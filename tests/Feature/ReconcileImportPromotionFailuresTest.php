<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportPromotionEvent;
use App\Models\ImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReconcileImportPromotionFailuresTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_are_grouped_by_stable_row_and_historical_events_are_not_current_state(): void
    {
        $batch = ImportBatch::create(['batch_key'=>'failure-audit-test','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>1]);
        $row = ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'rejected','match_state'=>'unresolved','payload'=>['name'=>'Test Institute'],'validation_errors'=>['name'=>'missing']]);
        foreach ([['duplicate_identity_collision','Identity collision'],['validation_failed','Required field missing']] as [$type,$reason]) {
            ImportPromotionEvent::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'entity_type'=>'institution','event_type'=>$type,'reason'=>$reason,'occurred_at'=>now(),'metadata'=>['test'=>true]]);
        }
        $eventsBefore = ImportPromotionEvent::count();
        $rowsBefore = ImportRow::count();

        $exit = Artisan::call('institutes:reconcile-promotion-failures', ['--json'=>true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertSame(2, $report['summary']['source_event_count']);
        $this->assertSame(1, $report['summary']['unique_stable_row_count']);
        $this->assertSame(1, $report['summary']['duplicate_or_identity_collision_event_count']);
        $this->assertSame(1, $report['summary']['validation_or_missing_field_event_count']);
        $this->assertSame(2, $report['rows'][0]['historical_event_count']);
        $this->assertCount(2, $report['rows'][0]['event_categories']);
        $this->assertTrue($report['rows'][0]['current_state_requires_review']);
        $this->assertFalse($report['mutation_performed']);
        $this->assertSame(0, $report['database_mutations']);
        $this->assertSame($eventsBefore, ImportPromotionEvent::count());
        $this->assertSame($rowsBefore, ImportRow::count());
    }

    public function test_batch_filter_limits_the_event_snapshot(): void
    {
        $first = ImportBatch::create(['batch_key'=>'failure-filter-a','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>1]);
        $second = ImportBatch::create(['batch_key'=>'failure-filter-b','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>1]);
        foreach ([$first, $second] as $batch) {
            $row = ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'received','match_state'=>'unresolved','payload'=>['name'=>'Institute '.$batch->id]]);
            ImportPromotionEvent::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'entity_type'=>'institution','event_type'=>'validation_failed','reason'=>'missing required name','occurred_at'=>now()]);
        }

        $exit = Artisan::call('institutes:reconcile-promotion-failures', ['--batch-id'=>$first->id,'--json'=>true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertSame(1, $report['summary']['source_event_count']);
        $this->assertSame([$first->id], $report['rows'][0]['batch_ids']);
        $this->assertFalse($report['mutation_performed']);
    }
}