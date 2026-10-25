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

    public function test_preflight_reports_duplicate_program_ids_even_when_candidate_campuses_differ(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'offering-gap-register-');
        $this->assertNotFalse($path);

        $handle = fopen($path, 'wb');
        $this->assertNotFalse($handle);

        try {
            fputcsv($handle, [
                'program_id', 'institution_id', 'program_name', 'official_source_url',
                'source_status', 'campus_status', 'remediation_decision',
                'candidate_campus_id', 'candidate_campus_name',
                'candidate_delivery_mode', 'campus_source_url',
            ]);
            fputcsv($handle, [
                '999991', '999992', 'Test programme', 'https://university.example.edu/programme',
                'source-found', 'confirmed', 'ready-for-validation',
                '999993', 'Campus A', 'on-campus', 'https://university.example.edu/campus-a',
            ]);
            fputcsv($handle, [
                '999991', '999992', 'Test programme', 'https://university.example.edu/programme',
                'source-found', 'confirmed', 'ready-for-validation',
                '999994', 'Campus B', 'on-campus', 'https://university.example.edu/campus-b',
            ]);
            fclose($handle);

            $exitCode = Artisan::call('institutes:offering-gap-preflight', [
                '--register' => $path,
                '--json' => true,
            ]);
            $report = json_decode(Artisan::output(), true);

            $this->assertSame(0, $exitCode);
            $this->assertSame(2, $report['summary']['register_rows'] ?? null);
            $this->assertSame(1, $report['summary']['duplicate_program_id_in_register'] ?? null);
            $this->assertContains(
                'duplicate_program_id_in_register',
                $report['records'][1]['blockers'] ?? []
            );
            $this->assertSame(0, $report['database_mutations'] ?? null);
            $this->assertFalse($report['mutation_performed'] ?? true);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }


    public function test_preflight_rejects_non_http_official_evidence_urls(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'offering-gap-register-');
        $this->assertNotFalse($path);
        $handle = fopen($path, 'wb');
        $this->assertNotFalse($handle);

        try {
            fputcsv($handle, [
                'program_id', 'institution_id', 'program_name', 'official_source_url',
                'source_status', 'campus_status', 'remediation_decision',
                'candidate_campus_id', 'candidate_campus_name',
                'candidate_delivery_mode', 'campus_source_url',
            ]);
            fputcsv($handle, [
                '999991', '999992', 'Test programme', 'javascript:alert(1)',
                'source-found', 'confirmed', 'ready-for-validation',
                '999993', 'Campus A', 'on-campus', 'https://campus.example.edu/campus-a',
            ]);
            fclose($handle);

            $exitCode = Artisan::call('institutes:offering-gap-preflight', [
                '--register' => $path,
                '--json' => true,
            ]);
            $report = json_decode(Artisan::output(), true);

            $this->assertSame(0, $exitCode);
            $this->assertFalse($report['records'][0]['official_evidence_url_valid'] ?? true);
            $this->assertTrue($report['records'][0]['campus_evidence_url_valid'] ?? false);
            $this->assertFalse($report['records'][0]['evidence_gate_passed'] ?? true);
            $this->assertContains(
                'invalid_official_programme_evidence_url',
                $report['records'][0]['blockers'] ?? []
            );
            $this->assertSame(0, $report['database_mutations'] ?? null);
            $this->assertFalse($report['mutation_performed'] ?? true);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }

}
