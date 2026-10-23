<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneInstituteDiscoveryReviewPackages extends Command
{
    protected $signature = 'institutes:prune-discovery-review-packages
        {--days=90 : Retain packages newer than this number of days}
        {--apply : Delete eligible packages; without this option, only report them}';

    protected $description = 'Report or prune old private institute discovery review packages.';

    public function handle(): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($days === false) {
            $this->error('The --days option must be a positive integer.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $root = 'institute-discovery-review-packages';
        if (! $disk->exists($root)) {
            $this->info('No review-package directory exists; nothing to prune.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days)->timestamp;
        $eligible = [];
        foreach ($disk->directories($root) as $directory) {
            if (! preg_match('/^'.preg_quote($root, '/').'\/snapshot-[1-9][0-9]*$/', $directory)) {
                continue;
            }

            $manifest = $directory.'/manifest.json';
            $csv = $directory.'/candidates.csv';
            if (! $disk->exists($manifest) || ! $disk->exists($csv)) {
                continue;
            }

            $createdAt = $disk->lastModified($manifest);
            if ($createdAt < $cutoff) {
                $eligible[] = $directory;
            }
        }

        $this->info(sprintf(
            '%d package(s) older than %d days; mode: %s.',
            count($eligible),
            $days,
            $this->option('apply') ? 'APPLY' : 'DRY RUN'
        ));

        foreach ($eligible as $directory) {
            $this->line($directory);
            if ($this->option('apply')) {
                $disk->deleteDirectory($directory);
            }
        }

        if (! $this->option('apply')) {
            $this->comment('Dry run only. Pass --apply to delete the listed package directories.');
        }

        return self::SUCCESS;
    }
}
