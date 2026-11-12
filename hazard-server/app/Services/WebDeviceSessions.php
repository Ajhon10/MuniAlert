<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebDeviceSessions
{
    public const COOKIE = 'munialert_device';

    private function device(Request $request): ?object
    {
        $value = $request->cookie(self::COOKIE);
        if (! is_string($value) || ! preg_match('/^[a-f0-9-]{36}\.[A-Za-z0-9]{64}$/D', $value)) {
            return null;
        }
        [$id, $proof] = explode('.', $value);
        $device = DB::table('web_device_sessions')->where('id', $id)->first();

        return $device && hash_equals($device->token_hash, hash('sha256', $proof)) ? $device : null;
    }

    private function cookie(string $proof): void
    {
        // Browser retention limits still apply; renew this HttpOnly cookie on use.
        Cookie::queue(Cookie::make(self::COOKIE, $proof, 525600, '/', config('session.domain'), app()->environment('production') || config('session.secure'), true, false, 'lax'));
    }

    public function issue(Request $request, User $user, bool $mfa): void
    {
        $this->revokeCurrent($request);
        $id = (string) Str::uuid();
        $proof = Str::random(64);
        DB::table('web_device_sessions')->insert(['id' => $id, 'user_id' => $user->id, 'token_hash' => hash('sha256', $proof), 'security_version' => $user->security_version, 'mfa_verified_at' => $mfa ? now() : null, 'last_used_at' => now(), 'created_at' => now()]);
        $request->session()->put('security.device_id', $id);
        $this->cookie($id.'.'.$proof);
    }

    public function restore(Request $request): void
    {
        if (! $request->hasSession() || $request->bearerToken()) {
            return;
        }
        $device = $this->device($request);
        if (! $device) {
            return;
        }
        $user = User::find($device->user_id);
        if (! $user || ! $user->is_active || $user->security_version !== (int) $device->security_version
            || ($user->role === 'admin' && (! $user->mfa_enabled || ! $device->mfa_verified_at))) {
            return;
        }
        $guard = Auth::guard('web');
        if ($guard->check() && $guard->id() !== $user->id) {
            return;
        }
        if (! $guard->check()) {
            $guard->login($user);
            $request->session()->regenerate();
        }
        if (! $request->session()->has('security.device_id')) {
            $request->session()->put('security', ['version' => $user->security_version, 'device_id' => $device->id, 'mfa_at' => $device->mfa_verified_at ? strtotime($device->mfa_verified_at) : null]);
        }
        if (strtotime($device->last_used_at) < now()->subHour()->timestamp) {
            DB::table('web_device_sessions')->where('id', $device->id)->update(['last_used_at' => now()]);
            $this->cookie($request->cookie(self::COOKIE));
        }
    }

    public function valid(Request $request, User $user): bool
    {
        // A live server-side session is already a credential. Its registered
        // device row must still exist; the proof cookie is needed for restoration.
        $device = DB::table('web_device_sessions')->where('id', $request->session()->get('security.device_id'))->first();

        return $device && (int) $device->user_id === $user->id && (int) $device->security_version === $user->security_version
            && $request->session()->get('security.device_id') === $device->id;
    }

    public function revokeCurrent(Request $request): void
    {
        if ($device = $this->device($request)) {
            DB::table('web_device_sessions')->where('id', $device->id)->delete();
        }
        Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));
    }

    public function invalidate(User $user, ?Request $keep): void
    {
        $id = $keep?->hasSession() ? $keep->session()->get('security.device_id') : null;
        DB::table('web_device_sessions')->where('user_id', $user->id)->when($id, fn ($query) => $query->where('id', '!=', $id))->delete();
        if ($id) {
            DB::table('web_device_sessions')->where('id', $id)->where('user_id', $user->id)->update(['security_version' => $user->security_version]);
        }
    }
}
