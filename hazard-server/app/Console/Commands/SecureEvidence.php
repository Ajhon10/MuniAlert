<?php

namespace App\Console\Commands;

use App\Models\HazardPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecureEvidence extends Command
{
    protected $signature = 'hazard:secure-evidence {--apply : Copy legacy public files into private local evidence, verify bytes and remove public copies}';

    protected $description = 'Inspect or secure legacy local public evidence without deleting report records';

    public function handle(): int
    {
        $query = HazardPhoto::whereIn('disk', ['public', 'evidence']);
        $this->info('Legacy local public evidence records: '.HazardPhoto::where('disk', 'public')->count());
        if (! $this->option('apply')) {
            $this->line('Inspection only. Use --apply to secure these files. R2 bucket access must be reviewed separately.');

            return self::SUCCESS;
        }
        $moved = 0;
        foreach ($query->lazyById(100) as $photo) {
            try {
                $source = Storage::disk('public');
                $target = Storage::disk('evidence');
                // Keep file and row changes serialized with another repair process.
                DB::transaction(function () use ($photo, $source, $target, &$moved) {
                    $locked = HazardPhoto::whereKey($photo->id)->lockForUpdate()->firstOrFail();
                    if (! $source->exists($locked->path) && $locked->disk === 'evidence') {
                        return;
                    }
                    $bytes = $source->get($locked->path);
                    if (! is_string($bytes) || ! $target->put($locked->path, $bytes)
                        || ! hash_equals(hash('sha256', $bytes), hash('sha256', $target->get($locked->path)))) {
                        throw new \RuntimeException('Evidence copy could not be verified.');
                    }
                    $locked->update(['disk' => 'evidence']);
                    $moved++;
                });
                // Delete only after the row commit. A crash leaves a retryable public
                // copy; later runs inspect evidence rows too and verify before removal.
                if ($source->exists($photo->path) && ! $source->delete($photo->path)) {
                    throw new \RuntimeException('Public copy could not be removed.');
                }
            } catch (\Throwable $error) {
                report($error);
                $this->error('Evidence repair stopped. Inspect private and legacy copies before retrying; no report records were deleted.');

                return self::FAILURE;
            }
        }
        $this->info('Evidence files secured: '.$moved.'. R2 bucket access must be reviewed separately.');

        return self::SUCCESS;
    }
}
