<?php
namespace Tests\Feature;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\User;
use App\Services\ImportBatchReconciliationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class OperationsDashboardTest extends TestCase {
 use RefreshDatabase;
 public function test_batch_report_summarizes_reconciliation_states(): void {
  $b=ImportBatch::create(['batch_key'=>'dashboard-test','entity_type'=>'institution','source_type'=>'primary']);
  ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'New']]);
  ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>2,'status'=>'invalid','match_state'=>'unresolved','payload'=>['name'=>''],'validation_errors'=>['name required']]);
  $s=app(ImportBatchReconciliationReport::class)->build($b);
  $this->assertSame(1,$s['new_candidate']); $this->assertSame(1,$s['invalid']); $this->assertSame(1,$s['ready']);
 }
 public function test_bulk_promotion_without_approval_does_not_create_canonical_data(): void {
  $user=User::create(['name'=>'Operator','email'=>'operator@example.com','password'=>bcrypt('password'),'role'=>'operator']);
  $b=ImportBatch::create(['batch_key'=>'bulk-test','entity_type'=>'institution','source_type'=>'primary']);
  $row=ImportRow::create(['import_batch_id'=>$b->id,'row_number'=>1,'status'=>'candidate','match_state'=>'new_candidate','payload'=>['name'=>'New University'],'normalized_payload'=>['name'=>'New University','slug'=>'new-university']]);
  $before = Institution::query()->count();
  $this->actingAs($user)->post(route('institutes.operations.bulk-promote'),['row_ids'=>[$row->id]])->assertSessionHas('status');
  $this->assertSame($before, Institution::query()->count());
  $this->assertSame('candidate', $row->fresh()->status);
 }
}