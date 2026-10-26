<?php

namespace Tests\\Feature;

use App\\Models\\ImportBatch;
use App\\Models\\ImportPromotionEvent;
use App\\Models\\ImportRow;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class ReconcileImportPromotionStructuredMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_structured_category_takes_precedence_over_free_text(): void
    {
        $batch=ImportBatch::create(['batch_key'=>'structured-category-test','entity_type'=>'institution','source_type'=>'manual','status'=>'completed','total_rows'=>1]);
        $row=ImportRow::create(['import_batch_id'=>$batch->id,'row_number'=>1,'status'=>'rejected','match_state'=>'unresolved','payload'=>['name'=>'Example']]);
        ImportPromotionEvent::create(['import_row_id'=>$row->id,'import_batch_id'=>$batch->id,'entity_type'=>'institution','event_type'=>'legacy_event','reason'=>'operator note','metadata'=>['failure_category'=>'validation_or_missing_field'],'occurred_at'=>now()]);
        $exit=Artisan::call('institutes:reconcile-promotion-failures',['--json'=>true]);
        $report=json_decode(Artisan::output(),true);
        $this->assertSame(0,$exit);
        $this->assertSame(1,$report['summary']['validation_or_missing_field_event_count']);
        $this->assertSame(0,$report['summary']['other_or_unclassified_event_count']);
        $this->assertSame($report['summary']['source_event_count'],$report['summary']['classified_event_count']);
        $this->assertSame(0,$report['database_mutations']);
    }
}