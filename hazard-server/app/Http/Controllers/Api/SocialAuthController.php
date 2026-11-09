<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialAccounts;
use App\Support\SocialAuthFailure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class SocialAuthController extends Controller
{
    private const NATIVE_RETURN = 'ph.municipal.bantay://auth/callback';

    private function enabled(string $provider): bool
    {
        $config = config('services.'.$provider);
        $uri = $config['redirect'] ?? '';

        return ! empty($config['client_id']) && ! empty($config['client_secret'])
            && filter_var($uri, FILTER_VALIDATE_URL)
            && (str_starts_with($uri, 'https://') || (app()->environment(['local', 'testing']) && in_array(parse_url($uri, PHP_URL_HOST), ['localhost', '127.0.0.1'], true) && str_starts_with($uri, 'http://')));
    }

    public function providers()
    {
        return response()->json(collect(['google', 'facebook'])->mapWithKeys(fn ($provider) => [$provider => ['enabled' => $this->enabled($provider)]]))->header('Cache-Control', 'no-store');
    }

    public function accounts(Request $request)
    {
        return ['providers' => DB::table('social_accounts')->where('user_id', $request->user()->id)->pluck('provider')];
    }

    public function prepare(Request $request, string $provider)
    {
        abort_unless($this->enabled($provider), 503, ucfirst($provider).' sign-in is not available right now. Use email to continue.');
        $data = $request->validate([
            'platform' => ['required', Rule::in(['web', 'android'])],
            'mode' => ['required', Rule::in(['login', 'link'])],
            'return_origin' => 'nullable|required_if:platform,web|url:http,https|max:255',
            'intent' => ['nullable', Rule::in(['overview', 'report', 'profile', 'mine', 'activity', 'news', 'reports', 'map', 'guide'])],
            'code_challenge' => ['nullable', 'required_if:platform,android', 'regex:/^[A-Za-z0-9_-]{43}$/'],
            'current_password' => 'nullable|string|max:128',
        ]);
        $origin = null;
        if ($data['platform'] === 'web') {
            $origin = rtrim($data['return_origin'], '/');
            $parts = parse_url($origin);
            $allowed = [...config('cors.allowed_origins', []), config('app.url')];
            abort_unless(! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment']) && empty($parts['path'])
                && in_array(strtolower($origin), array_map(fn ($value) => strtolower(rtrim((string) $value, '/')), $allowed), true), 422, 'This return address is not allowed.');
        }
        $actor = null;
        $token = null;
        if ($data['mode'] === 'link') {
            $actor = Auth::guard('sanctum')->user();
            abort_unless($actor, 401);
            abort_unless($actor->has_password && Hash::check($data['current_password'] ?? '', $actor->password), 422, 'Confirm your MuniAlert password to connect this account.');
            $token = $actor->currentAccessToken();
        }
        $id = (string) Str::uuid();
        DB::table('social_auth_attempts')->insert([
            'id' => $id, 'provider' => $provider, 'platform' => $data['platform'], 'mode' => $data['mode'],
            'return_origin' => $origin, 'intent' => $data['intent'] ?? 'overview', 'code_challenge' => $data['code_challenge'] ?? null,
            'link_user_id' => $actor?->id, 'auth_fingerprint' => $actor ? $this->fingerprint($actor) : null,
            'access_token_id' => $token instanceof PersonalAccessToken ? $token->id : null,
            'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['attempt' => $id, 'redirect_url' => url('/auth/social/'.$provider.'/redirect').'?attempt='.$id], 201)->header('Cache-Control', 'no-store');
    }

    public function redirect(Request $request, string $provider)
    {
        abort_unless($this->enabled($provider), 503, 'This sign-in method is not available.');
        $request->validate(['attempt' => 'required|uuid']);
        $id = $request->query('attempt');
        $started = DB::table('social_auth_attempts')->where('id', $id)->where('provider', $provider)->whereNull('started_at')->whereNull('consumed_at')->where('expires_at', '>', now())->update(['started_at' => now(), 'updated_at' => now()]);
        abort_unless($started === 1, 422, 'This sign-in request expired. Return to MuniAlert and start again.');
        $request->session()->put('social_auth_attempt', $id);

        return $this->driver($provider)->redirect()->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    public function callback(Request $request, string $provider, SocialAccounts $accounts)
    {
        $id = $request->session()->pull('social_auth_attempt');
        $attempt = $id ? DB::table('social_auth_attempts')->where('id', $id)->where('provider', $provider)->first() : null;
        if (! $attempt || $attempt->consumed_at || $attempt->completed_at || Carbon::parse($attempt->expires_at)->isPast()) {
            return response('This sign-in request expired. Return to MuniAlert and try again.', 422)->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
        }
        try {
            if ($request->has('error')) {
                $state = $request->session()->pull('state');
                if (! is_string($state) || ! is_string($request->query('state')) || ! hash_equals($state, $request->query('state'))) {
                    throw new SocialAuthFailure('expired');
                }
                throw new SocialAuthFailure('cancelled');
            }
            if (! $this->enabled($provider)) {
                throw new SocialAuthFailure('unavailable');
            }
            // Keep Socialite's session-bound state protection enabled.
            $identity = $this->driver($provider)->user();
            $code = Str::random(64);
            $user = DB::transaction(function () use ($attempt, $accounts, $identity, $provider, $code) {
                $current = DB::table('social_auth_attempts')->where('id', $attempt->id)->lockForUpdate()->first();
                if ($current->consumed_at || $current->completed_at || Carbon::parse($current->expires_at)->isPast()) {
                    throw new SocialAuthFailure('expired');
                }
                $link = null;
                if ($current->mode === 'link') {
                    $link = User::whereKey($current->link_user_id)->lockForUpdate()->first();
                    if (! $link || ! hash_equals($current->auth_fingerprint, $this->fingerprint($link))
                        || ($current->platform === 'web' && Auth::guard('web')->id() !== $link->id)) {
                        throw new SocialAuthFailure('expired');
                    }
                    if ($current->access_token_id) {
                        $token = PersonalAccessToken::find($current->access_token_id);
                        if (! $token || (int) $token->tokenable_id !== $link->id || ($token->expires_at && $token->expires_at->isPast())) {
                            throw new SocialAuthFailure('expired');
                        }
                    }
                }
                $user = $accounts->resolve($provider, $identity, $link);
                DB::table('social_auth_attempts')->where('id', $attempt->id)->update([
                    'user_id' => $user->id, 'completed_at' => now(), 'updated_at' => now(),
                    'consumed_at' => $attempt->platform === 'web' ? now() : null,
                    'exchange_code_hash' => $attempt->platform === 'android' ? hash('sha256', $code) : null,
                    'expires_at' => now()->addSeconds(90),
                ]);

                return $user;
            });
            if ($attempt->platform === 'web' && $attempt->mode === 'login') {
                Auth::guard('web')->setRememberDuration(config('hazards.session_days') * 1440)->login($user, true);
                $request->session()->regenerate();
            }

            return $this->finish($attempt, ['social_auth' => $attempt->mode === 'link' ? 'linked' : 'success', ...($attempt->platform === 'android' ? ['code' => $code] : [])]);
        } catch (SocialAuthFailure $error) {
            $reason = $error->reason;
        } catch (InvalidStateException) {
            $reason = 'expired';
        } catch (Throwable $error) {
            // Exception messages can contain provider authorization codes/tokens.
            Log::warning('Social authentication failed', ['provider' => $provider, 'exception_class' => get_class($error)]);
            $reason = 'provider_failed';
        } finally {
            $request->session()->forget(['state', 'code_verifier']);
        }
        DB::table('social_auth_attempts')->where('id', $attempt->id)->update(['consumed_at' => now(), 'updated_at' => now()]);

        return $this->finish($attempt, ['social_auth' => 'error', 'social_error' => $reason]);
    }

    public function exchange(Request $request)
    {
        $data = $request->validate(['attempt' => 'required|uuid', 'code' => 'required|string|size:64', 'code_verifier' => ['required', 'string', 'regex:/^[A-Za-z0-9._~-]{43,128}$/']]);
        $result = DB::transaction(function () use ($data) {
            $attempt = DB::table('social_auth_attempts')->where('id', $data['attempt'])->where('platform', 'android')->lockForUpdate()->first();
            if (! $attempt || $attempt->consumed_at || ! $attempt->completed_at || Carbon::parse($attempt->expires_at)->isPast()) {
                return null;
            }
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $data['code_verifier'], true)), '+/', '-_'), '=');
            if (! hash_equals($attempt->exchange_code_hash, hash('sha256', $data['code'])) || ! hash_equals($attempt->code_challenge, $challenge)) {
                $failures = $attempt->failures + 1;
                DB::table('social_auth_attempts')->where('id', $attempt->id)->update(['failures' => $failures, 'consumed_at' => $failures >= 5 ? now() : null]);

                return null;
            }
            $user = User::whereKey($attempt->user_id)->lockForUpdate()->first();
            if (! $user) {
                return null;
            }
            DB::table('social_auth_attempts')->where('id', $attempt->id)->update(['consumed_at' => now(), 'exchange_code_hash' => null, 'updated_at' => now()]);

            return ['user' => $user, 'intent' => $attempt->intent, 'linked' => $attempt->mode === 'link', ...($attempt->mode === 'login' ? ['token' => $user->createToken('mobile', ['*'], now()->addDays(config('hazards.session_days')))->plainTextToken] : [])];
        });
        abort_unless($result, 422, 'This sign-in request expired or could not be verified. Start again.');

        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    private function fingerprint(User $user): string
    {
        return hash('sha256', $user->password.'|'.$user->remember_token);
    }

    private function driver(string $provider)
    {
        $driver = Socialite::driver($provider)->setHttpClient(new Client(['connect_timeout' => 5, 'timeout' => 15]));

        return $provider === 'google' ? $driver->enablePKCE() : $driver->fields(['id', 'name', 'email']);
    }

    private function finish(object $attempt, array $parameters)
    {
        $parameters['social_intent'] = $attempt->intent;
        $parameters['provider'] = $attempt->provider;
        $target = $attempt->platform === 'android'
            ? self::NATIVE_RETURN.'?'.http_build_query(['attempt' => $attempt->id, ...$parameters])
            : $attempt->return_origin.'/?'.http_build_query($parameters);

        return redirect()->away($target)->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }
}
