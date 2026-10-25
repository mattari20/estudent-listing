<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Services\InstituteDiscoveryAgentService;
use Illuminate\Console\Command;
use Throwable;

class RefreshInstituteDiscoveryBatch extends Command
{
    protected $signature = 'institutes:refresh-discovery
        {profile : Configured discovery profile to refresh}
        {--supersedes-batch-id= : Existing batch from this profile to retain as history}
        {--confirm-new-batch : Required explicit confirmation to create a new immutable review batch}';

    protected $description = 'Create a new read-only review batch from a configured source while preserving the prior batch and provenance.';

    public function handle(InstituteDiscoveryAgentService $agent): int
    {
        $profile = (string) $this->argument('profile');
        $definition = config("institute_agent.profiles.{$profile}");

        if (! is_array($definition)) {
            $this->error('Unknown discovery profile: '.$profile);

            return self::FAILURE;
        }

        if (! $this->option('confirm-new-batch')) {
            $this->error('No batch was created. Re-run with --confirm-new-batch after reviewing the profile and superseded batch ID.');

            return self::FAILURE;
        }

        $supersedesBatchId = filter_var(
            $this->option('supersedes-batch-id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($supersedesBatchId === false) {
            $this->error('A valid --supersedes-batch-id is required; old batches are never overwritten or deleted.');

            return self::FAILURE;
        }

        $priorBatch = ImportBatch::query()->find($supersedesBatchId);
        if (! $priorBatch) {
            $this->error('The requested prior batch does not exist.');

            return self::FAILURE;
        }

        try {
            // Deliberately deterministic: this command never opts into a paid AI API.
            $summary = $agent->run($profile, false, true, (int) $supersedesBatchId);
        } catch (Throwable $exception) {
            $this->error('Refresh failed closed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['profile', $profile],
                ['prior_batch_preserved', $priorBatch->id],
                ['new_batch_id', $summary['batch_id']],
                ['source_snapshot_id', $summary['snapshot_id']],
                ['total_rows', $summary['total_rows']],
                ['candidate_rows', $summary['candidate_rows']],
                ['duplicate_rows', $summary['duplicate_rows']],
                ['review_package_status', $summary['review_package']['status'] ?? 'unavailable'],
                ['canonical_mutations_performed', 'false'],
                ['automatic_approval_or_promotion', 'false'],
            ]
        );

        return self::SUCCESS;
    }
}
