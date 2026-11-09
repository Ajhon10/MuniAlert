<?php

namespace App\Support;

use RuntimeException;

class SocialAuthFailure extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
