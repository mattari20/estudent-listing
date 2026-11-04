<?php

namespace App\Console\Commands;

use App\Services\InstituteDiscoveryReviewPackageService;
use Illuminate\Console\Command;
use Throwable;

class PruneInstituteDiscoveryReviewPackages extends Command
{
    protected $signature = 'institutes:prune-discovery-review-packages
        {--min-age-hours=168 : Minimum age before an orphan artifact is eligible}
        {--apply : Delete eligible orphan artifacts; without this option, report only}';

    protected $description = 'Safely reconcile stale orphaned private institute discovery review-package artifacts.';

    public function handle(InstituteDiscoveryReviewPackageService $packages): int
    {
        $hours = filter_var($this->option('min-age-hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 24]]);
        if ($hours === false) {
            $this->error('The --min-age-hours option must be an integer of at least 24.');

            return self::FAILURE;
        }

        try {
            $apply = (bool) $this->option('apply');
            $report = $packages->reconcileOrphans($hours, $apply);
        } catch (Throwable $exception) {
            $this->error('Review-package reconciliation failed closed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Review-package reconciliation: %d eligible, %d skipped, %d deleted; mode: %s; minimum age: %d hours.',
            count($report['candidates']),
            count($report['skipped']),
            count($report['deleted']),
            $apply ? 'APPLY' : 'DRY RUN',
            $hours
        ));

        foreach ($report['candidates'] as $item) {
            $this->line('ELIGIBLE '.$item['path'].' — '.$item['reason']);
            foreach ($item['candidate_paths'] ?? [] as $path) {
                $this->line('  '.$path);
            }
        }
        foreach ($report['deleted'] as $item) {
            $this->line('DELETED '.$item['path'].' — '.$item['reason']);
            foreach ($item['candidate_paths'] ?? [] as $path) {
                $this->line('  '.$path);
            }
        }
        foreach ($report['skipped'] as $item) {
            $this->comment('SKIPPED '.$item['path'].' — '.$item['reason']);
        }

        if (! $apply) {
            $this->comment('Dry run only. Pass --apply to remove only listed, old, unambiguous orphan artifacts.');
        }

        return self::SUCCESS;
    }
}
