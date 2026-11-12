<?php

namespace App\Support;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;

class SecurityLock
{
    public static function make(string $name, int $seconds): Lock
    {
        $store = Cache::store('database')->getStore();
        if (! $store instanceof LockProvider) {
            throw new \RuntimeException('A shared database lock provider is required.');
        }

        return $store->lock($name, $seconds);
    }
}
