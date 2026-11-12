<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class SecuritySessions
{
    public function issue(Request $request, User $user, bool $mfa = false, ?bool $native = null): array
    {
        abort_unless($user->is_active, 401, 'Sign-in could not be completed.');
        abort_if($user->role === 'admin' && (! $mfa || ! $user->mfa_enabled), 403, 'Administrator verification is required.');
        $native ??= $request->is('api/auth/*');
        if ($native) {
            $credential = $user->createToken('mobile', ['*'], config('hazards.persistent_login') ? null : now()->addDays(config('hazards.session_days')));
            $credential->accessToken->forceFill([
                'security_version' => $user->security_version,
                'mfa_verified_at' => $mfa ? now() : null,
                'reauthenticated_at' => now(),
            ])->save();
            $result = ['user' => $user, 'token' => $credential->plainTextToken];
        } else {
            Auth::guard('web')->setRememberDuration(config('hazards.session_days') * 1440)->login($user, true);
            $request->session()->regenerate();
            $request->session()->put('security', [
                'version' => $user->security_version,
                'mfa_at' => $mfa ? now()->timestamp : null,
                'reauth_at' => now()->timestamp,
            ]);
            app(WebDeviceSessions::class)->issue($request, $user, $mfa);
            $result = ['user' => $user];
        }
        app(SecurityEvents::class)->record('login_succeeded', $user, $request);

        return $result;
    }

    public function valid(Request $request, User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $current = PersonalAccessToken::find($token->id);

            return $current !== null && (! $current->expires_at || $current->expires_at->isFuture()) && (int) $current->security_version === $user->security_version
                && ($user->role !== 'admin' || ($user->mfa_enabled && $current->mfa_verified_at));
        }

        // Legacy citizen sessions with version zero survive this additive release.
        return $request->hasSession() && (! $request->session()->has('security.device_id') || app(WebDeviceSessions::class)->valid($request, $user))
            && (! Auth::guard('web')->viaRemember() || $request->session()->has('security.device_id'))
            && (int) $request->session()->get('security.version', 0) === $user->security_version
            && ($user->role !== 'admin' || ($user->mfa_enabled && $request->session()->get('security.mfa_at')));
    }

    public function recent(Request $request): bool
    {
        $token = $request->user()->currentAccessToken();
        $time = $token instanceof PersonalAccessToken
            ? DB::table('personal_access_tokens')->where('id', $token->id)->value('reauthenticated_at')
            : ($request->hasSession() ? $request->session()->get('security.reauth_at') : null);

        return $time && (is_numeric($time) ? Carbon::createFromTimestamp((int) $time) : Carbon::parse($time))->gt(now()->subMinutes(config('security.reauth_minutes')));
    }

    public function requireRecent(Request $request): void
    {
        if (! $this->recent($request)) {
            abort(response()->json(['message' => 'Verify your identity to continue.', 'reauthentication_required' => true,
                'password_required' => $request->user()->has_password, 'mfa_required' => $request->user()->mfa_enabled], 428));
        }
    }

    public function stampRecent(Request $request): void
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->forceFill(['reauthenticated_at' => now()])->save();
        } else {
            $request->session()->put('security.reauth_at', now()->timestamp);
        }
    }

    public function invalidate(User $user, ?Request $keep = null): void
    {
        $current = $keep?->user()?->currentAccessToken();
        $user->security_version++;
        $user->remember_token = Str::random(60);
        $user->save();
        app(WebDeviceSessions::class)->invalidate($user, $keep);
        $user->tokens()->when($current instanceof PersonalAccessToken, fn ($query) => $query->whereKeyNot($current->id))->delete();
        DB::table('security_challenges')->where('user_id', $user->id)->update(['consumed' => true, 'payload' => null]);
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        if ($current instanceof PersonalAccessToken) {
            $current->forceFill(['security_version' => $user->security_version, 'reauthenticated_at' => null])->save();
        } elseif ($keep?->hasSession()) {
            $keep->session()->put('security.version', $user->security_version);
            $keep->session()->forget('security.reauth_at');
            $keep->session()->regenerate();
        }
    }
}
