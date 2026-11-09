<?php

namespace App\Services;

use App\Models\User;
use App\Support\SocialAuthFailure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccounts
{
    public function resolve(string $provider, ProviderUser $identity, ?User $link = null): User
    {
        $subject = (string) $identity->getId();
        if ($subject === '' || strlen($subject) > 255) {
            throw new SocialAuthFailure('provider_failed');
        }
        $account = DB::table('social_accounts')->where('provider', $provider)->where('provider_id', $subject)->lockForUpdate()->first();
        if ($account) {
            if ($link && (int) $account->user_id !== $link->id) {
                throw new SocialAuthFailure('account_in_use');
            }

            return User::whereKey($account->user_id)->lockForUpdate()->firstOrFail();
        }
        if ($link) {
            if (DB::table('social_accounts')->where('user_id', $link->id)->where('provider', $provider)->exists()) {
                throw new SocialAuthFailure('account_in_use');
            }
            $user = $link;
        } else {
            $email = strtolower(trim((string) $identity->getEmail()));
            if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new SocialAuthFailure('email_required');
            }
            $raw = method_exists($identity, 'getRaw') ? $identity->getRaw() : [];
            $verified = ($raw['email_verified'] ?? $raw['verified_email'] ?? false) === true;
            if ($provider === 'google' && ! $verified) {
                throw new SocialAuthFailure('email_unverified');
            }
            // Never merge by email. Existing password accounts must explicitly
            // authenticate and link; an unverified provider email is not proof.
            if (User::where('email', $email)->exists()) {
                throw new SocialAuthFailure('existing_account');
            }
            $user = User::create([
                'name' => mb_substr(trim((string) $identity->getName()) ?: 'Community member', 0, 120),
                'email' => $email,
                'password' => Str::random(64),
            ]);
            $user->forceFill(['password_enabled' => false, 'email_verified_at' => $provider === 'google' && $verified ? now() : null])->save();
        }
        DB::table('social_accounts')->insert(['user_id' => $user->id, 'provider' => $provider, 'provider_id' => $subject, 'created_at' => now(), 'updated_at' => now()]);

        return $user;
    }
}
