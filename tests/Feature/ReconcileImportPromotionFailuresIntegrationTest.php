<?php

namespace Tests\\Feature;

use App\\Models\\ImportBatch;
use App\\Models\\ImportPromotionEvent;
use App\\Models\\ImportRow;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class ReconcileImportPromotionFailuresIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_event_counts_reconcile_to_stable_row_event_ids(): void
    {
        $batch=ImportBatch::create(['batch_key'=>'reconcile-invariant-test','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>2]);
        $rowA=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'rejected','match_state'=>'unresolved','payload'=>['name'=>'A']]);
        $rowB=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>2,'status'=>'approved','match_state'=>'matched','payload'=>['name'=>'B']]);
        foreach ([[$rowA,'duplicate_identity_collision','identity collision'],[$rowA,'validation_failed','missing required field'],[$rowB,'legacy_unknown','operator note']] as [$row,$type,$reason]) {
            ImportPromotionEvent::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'entity_type'=>'institution','event_type'=>$type,'reason'=>$reason,'occurred_at'=>now()]);
        }
        $exit=Artisan::call('institutes:reconcile-promotion-failures',['--json'=>true]);
        $report=json_decode(Artisan::output(),true);
        $allIds=[];
        foreach($report['rows'] as $row) foreach($row['event_ids'] as $id) $allIds[]=$id;
        $this->assertSame(0,$exit);
        $this->assertSame(3,$report['summary']['source_event_count']);
        $this->assertSame(2,$report['summary']['unique_stable_row_count']);
        $this->assertCount(3,array_unique($allIds));
        $this->assertCount(3,$allIds);
        $this->assertSame(3,count($allIds)+$report['summary']['unresolved_event_count']);
        $this->assertSame(0,$report['database_mutations']);
        $this->assertFalse($report['mutation_performed']);
    }
}