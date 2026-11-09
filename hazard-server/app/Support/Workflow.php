<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Workflow
{
    public const NEXT = ['submitted' => ['under_verification', 'rejected'], 'under_verification' => ['verified', 'rejected'], 'verified' => ['assigned', 'rejected'], 'assigned' => ['responding'], 'responding' => ['resolved'], 'resolved' => ['closed'], 'closed' => [], 'rejected' => []];

    public static function check(string $from, string $to): void
    {
        if ($from === $to && ! in_array($from, ['closed', 'rejected'])) {
            return;
        }
        if (! in_array($to, self::NEXT[$from] ?? [])) {
            throw ValidationException::withMessages(['status' => "Cannot change $from to $to."]);
        }
    }
}
