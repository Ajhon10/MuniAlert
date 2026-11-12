<?php

namespace App\Support;

use Closure;

class PasswordInput
{
    /** Bcrypt accepts at most 72 bytes, including multibyte characters. */
    public static function rules(bool $newPassword = false): array
    {
        return [
            'string',
            ...($newPassword ? ['min:12'] : []),
            'max:128',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && str_contains($value, "\0")) {
                    $fail('The :attribute must not contain null bytes.');
                }
                if (is_string($value) && strlen($value) > 72) {
                    $fail('The :attribute must be at most 72 bytes. Use fewer characters if using accented letters or emoji.');
                }
            },
        ];
    }
}
