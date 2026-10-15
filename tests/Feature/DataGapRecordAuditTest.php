<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapRecordAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_audit_is_read_only_and_outputs_canonical_records(): void
    {
        Institution::query()->create([
            'name' => 'Record Audit Test Institute',
            'slug' => 'record-audit-test-institute',
            'institution_type' => 'university',
            'status' => 'candidate',
        ]);

        Artisan::call('institutes:data-gap-record-audit', ['--json' => true]);
        $output = Artisan::output();
        $report = json_decode($output, true);

        $this->assertIsArray($report);
        $this->assertSame('institutes_data_gap_record_audit', $report['check'] ?? null);
        $this->assertFalse($report['mutation_performed'] ?? true);
        $this->assertSame('Record Audit Test Institute', $report['sections']['institutions']['records'][0]['name'] ?? null);
        $this->assertStringNotContainsString('payload', json_encode($report['sections']['import_rows']['records'] ?? []));
        $this->assertSame(1, Institution::query()->where('slug', 'record-audit-test-institute')->count());
    }
}
