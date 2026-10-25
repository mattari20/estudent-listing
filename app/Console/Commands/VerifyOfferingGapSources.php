<?php

namespace App\Console\Commands;

use App\Services\OfficialSourcePageVerifier;
use Illuminate\Console\Command;

class VerifyOfferingGapSources extends Command
{
    protected $signature = 'institutes:verify-offering-gap-sources
        {--register=docs/step3-offering-gap-evidence-register.csv : Evidence register CSV}
        {--json : Print machine-readable JSON}';

    protected $description = 'Fetch official-source candidates safely and report reachability plus programme/campus text matches without database writes.';

    public function handle(OfficialSourcePageVerifier $verifier): int
    {
        $report = $verifier->verify((string) $this->option('register'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Metric', 'Value'],
                collect($report['summary'] ?? [])->map(fn ($value, $key) => [$key, $value])->values()->all()
            );
            $this->info('verification_status='.($report['verification_status'] ?? 'BLOCKED_SOURCE_VERIFICATION'));
            $this->line('database_mutations=0');
        }

        return ($report['errors'] ?? []) === [] ? self::SUCCESS : self::FAILURE;
    }
}
