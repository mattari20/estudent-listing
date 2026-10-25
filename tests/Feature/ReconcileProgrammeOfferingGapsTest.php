<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconcileProgrammeOfferingGapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_classifies_existing_and_missing_relationships_without_writes(): void
    {
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'test-university-main-campus',
            'campus_type' => 'main',
            'status' => 'active',
        ]);
        $existingProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'BS Existing',
            'slug' => 'bs-existing',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);
        $missingProgram = Program::create([
            'institution_id' => $institution->id,
            'name' => 'BS Missing',
            'slug' => 'bs-missing',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);
        ProgramOffering::create([
            'program_id' => $existingProgram->id,
            'campus_id' => $campus->id,
            'delivery_mode' => 'on_campus',
            'status' => 'active',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'offering-gap-reconcile-');
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
            foreach ([
                [$existingProgram->id, 'BS Existing'],
                [$missingProgram->id, 'BS Missing'],
            ] as [$programId, $programName]) {
                fputcsv($handle, [
                    $programId,
                    $institution->id,
                    $programName,
                    'https://university.example.edu/programmes',
                    'source-found',
                    'main-campus-confirmed',
                    'ready-for-validation',
                    $campus->id,
                    'Main Campus',
                    'on_campus',
                    'https://university.example.edu/campus',
                ]);
            }
            fclose($handle);

            $offeringsBefore = ProgramOffering::count();
            $exitCode = Artisan::call('institutes:offering-gap-reconcile', [
                '--register' => $path,
                '--expected-rows' => 2,
                '--json' => true,
            ]);
            $report = json_decode(Artisan::output(), true);

            $this->assertSame(0, $exitCode);
            $this->assertSame('ROW_LEVEL_REPORT_CREATED_REVIEW_REQUIRED', $report['reconciliation_status']);
            $this->assertSame(2, $report['summary']['register_rows']);
            $this->assertSame(1, $report['summary']['active_offering_already_exists']);
            $this->assertSame(1, $report['summary']['missing_relationship_candidates']);
            $this->assertSame('ACTIVE_OFFERING_ALREADY_EXISTS', $report['records'][0]['classification']);
            $this->assertSame(
                'MISSING_RELATIONSHIP_CANDIDATE_REQUIRES_SOURCE_REVIEW',
                $report['records'][1]['classification']
            );
            $this->assertFalse($report['mutation_performed']);
            $this->assertSame(0, $report['database_mutations']);
            $this->assertFalse($report['production_write_authorization']);
            $this->assertSame($offeringsBefore, ProgramOffering::count());
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }

    public function test_reconciliation_blocks_missing_evidence_urls_and_reports_register_count_mismatch(): void
    {
        $institution = Institution::create([
            'name' => 'Test University',
            'slug' => 'test-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'slug' => 'test-university-main-campus',
            'campus_type' => 'main',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'BS Evidence Gap',
            'slug' => 'bs-evidence-gap',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'offering-gap-reconcile-');
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
                $program->id,
                $institution->id,
                'BS Evidence Gap',
                '',
                'not-verified',
                'verify-current-campus',
                'blocked',
                $campus->id,
                'Main Campus',
                'on_campus',
                '',
            ]);
            fclose($handle);

            $exitCode = Artisan::call('institutes:offering-gap-reconcile', [
                '--register' => $path,
                '--expected-rows' => 36,
                '--json' => true,
            ]);
            $report = json_decode(Artisan::output(), true);

            $this->assertSame(0, $exitCode);
            $this->assertSame('BLOCKED_REGISTER_ROW_COUNT_MISMATCH', $report['reconciliation_status']);
            $this->assertSame('BLOCKED_INSUFFICIENT_OR_MISMATCHED_EVIDENCE', $report['records'][0]['classification']);
            $this->assertContains('invalid_or_missing_official_programme_url', $report['records'][0]['blockers']);
            $this->assertContains('invalid_or_missing_campus_url', $report['records'][0]['blockers']);
            $this->assertSame(0, $report['database_mutations']);
            $this->assertFalse($report['mutation_performed']);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($path);
        }
    }
}
