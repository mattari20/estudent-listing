<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Services\InstituteDiscoveryReviewPackageService;
use Illuminate\Console\Command;
use Throwable;

class CreateInstituteDiscoveryReviewPackage extends Command
{
    protected $signature = 'institutes:discovery-review-package {batch? : Discovery batch ID; defaults to the newest discovery batch}';

    protected $description = 'Create or reuse a private CSV and JSON manifest for human review of discovered candidates.';

    public function handle(InstituteDiscoveryReviewPackageService $packages): int
    {
        $query = ImportBatch::query()
            ->whereIn('source_type', ['automated_discovery', 'ai_extraction'])
            ->with(['source', 'rows'])
            ->orderByDesc('id');

        if ($this->argument('batch') !== null) {
            $query->whereKey((int) $this->argument('batch'));
        }

        $batch = $query->first();
        if (! $batch) {
            $this->error('No matching discovery batch was found.');

            return self::FAILURE;
        }

        try {
            $result = $packages->create($batch);
        } catch (Throwable $exception) {
            $this->error('Review package creation failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            collect($result)->map(fn ($value, $key) => [$key, $value])->values()->all()
        );
        $this->info('Package is stored on the private local disk; it is not publicly exposed.');

        return self::SUCCESS;
    }
}
