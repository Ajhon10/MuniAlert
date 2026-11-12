<?php

namespace App\Services;

class AccountMailDelivery
{
    public function available(): bool
    {
        if (! config('mail.enabled')) {
            return false;
        }
        if (app()->environment('testing')) {
            return true;
        }
        $mailer = config('mail.mailers.'.config('mail.default'));

        return is_array($mailer) && ($mailer['transport'] ?? null) === 'smtp'
            && filled($mailer['host'] ?? null) && filled($mailer['username'] ?? null) && filled($mailer['password'] ?? null)
            && ($mailer['require_tls'] ?? false) && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            && $this->recipientAllowed(config('mail.from.address'));
    }

    public function recipientAllowed(string $email): bool
    {
        if (app()->environment('testing')) {
            return true;
        }
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($domain, ['example.com', 'example.org', 'example.net', 'localhost'], true)
            && ! preg_match('/\.(test|invalid|example|localhost)$/', $domain);
    }
}
