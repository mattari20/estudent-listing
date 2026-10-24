<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfferingGapPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_preflight_fails_closed_when_evidence_register_is_missing_and_reports_zero_mutations(): void
    {
        $before = DB::table('programs')->count();

        $exitCode = Artisan::call('institutes:offering-gap-preflight', [
            '--register' => 'docs/does-not-exist-offering-gap-register.csv',
            '--json' => true,
        ]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertIsArray($report);
        $this->assertContains('register_file_missing', $report['errors'] ?? []);
        $this->assertSame(0, $report['database_mutations'] ?? null);
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertFalse($report['production_write_authorization'] ?? true);
        $this->assertSame($before, DB::table('programs')->count());
    }

    public function test_preflight_is_explicitly_read_only_when_required_tables_are_available(): void
    {
        Artisan::call('institutes:offering-gap-preflight', [
            '--register' => 'docs/does-not-exist-offering-gap-register.csv',
            '--json' => true,
        ]);

        $report = json_decode(Artisan::output(), true);

        $this->assertIsArray($report);
        $this->assertSame('institutes_offering_gap_preflight', $report['check'] ?? null);
        $this->assertSame('read_only', $report['mode'] ?? null);
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertSame(0, $report['database_mutations'] ?? null);
        $this->assertFalse($report['production_write_authorization'] ?? true);
        $this->assertContains('register_file_missing', $report['errors'] ?? []);
    }
}
