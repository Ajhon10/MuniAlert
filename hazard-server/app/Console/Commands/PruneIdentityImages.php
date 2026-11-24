<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneIdentityImages extends Command
{
    protected $signature = 'hazard:prune-identity';

    protected $description = 'Delete up to 100 expired private government-ID images';

    public function handle(): int
    {
        $ids = DB::table('identity_verifications')->where('status', 'pending')->where('expires_at', '<=', now())->orderBy('expires_at')->orderBy('id')->limit(100)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                $row = DB::table('identity_verifications')->where('id', $id)->lockForUpdate()->first();
                if ($row && $row->status === 'pending' && now()->gte($row->expires_at)) {
                    if ($row->path && ! Storage::disk('identity')->delete($row->path)) {
                        throw new \RuntimeException('Expired identity image could not be deleted.');
                    }
                    DB::table('identity_verifications')->where('id', $id)->update(['path' => null, 'status' => 'expired', 'updated_at' => now()]);
                }
            });
        }

        // A process can stop between file creation and its DB transaction, or an
        // operator can remove an account. Reap old unreferenced encrypted files.
        $disk = Storage::disk('identity');
        $checked = 0;
        foreach ($disk->getDriver()->listContents('', false) as $entry) {
            if (++$checked > 1000) {
                break;
            }
            if ($entry->isFile() && ($entry->lastModified() ?? PHP_INT_MAX) < now()->subDays(7)->timestamp
                && ! DB::table('identity_verifications')->where('path', $entry->path())->exists()
                && ! $disk->delete($entry->path())) {
                throw new \RuntimeException('Orphaned identity image could not be deleted.');
            }
        }

        return self::SUCCESS;
    }
}
