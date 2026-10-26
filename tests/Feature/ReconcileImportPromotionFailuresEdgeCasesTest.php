<?php

namespace Tests\\Feature;

use App\\Models\\ImportBatch;
use App\\Models\\ImportPromotionEvent;
use App\\Models\\ImportRow;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class ReconcileImportPromotionFailuresEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_event_types_are_preserved_as_unclassified_and_not_guessed(): void
    {
        $batch = ImportBatch::create(['batch_key'=>'failure-unknown-event','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>1]);
        $row = ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'approved','match_state'=>'matched','payload'=>['name'=>'Already reviewed']]);
        ImportPromotionEvent::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'entity_type'=>'institution','event_type'=>'legacy_event_x','reason'=>'operator note','occurred_at'=>now()]);

        $exit = Artisan::call('institutes:reconcile-promotion-failures', ['--json'=>true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertSame(['other_or_unclassified'], $report['rows'][0]['event_categories']);
        $this->assertFalse($report['rows'][0]['current_state_requires_review']);
        $this->assertSame('verify_current_state_before_closing_historical_event', $report['rows'][0]['proposed_disposition']);
        $this->assertTrue($report['rows'][0]['historical_failure_does_not_prove_current_unresolved_state']);
        $this->assertSame(0, $report['database_mutations']);
    }

    public function test_invalid_batch_filter_fails_closed_with_machine_readable_error(): void
    {
        $exit = Artisan::call('institutes:reconcile-promotion-failures', ['--batch-id'=>'0','--json'=>true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exit);
        $this->assertContains('invalid_batch_id', $report['errors']);
        $this->assertSame(0, $report['database_mutations']);
        $this->assertFalse($report['mutation_performed']);
    }
}