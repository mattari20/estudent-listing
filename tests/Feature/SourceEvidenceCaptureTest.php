<?php
namespace Tests\Feature;
use App\Models\Assertion;
use App\Models\Source;
use App\Models\SourceSnapshot;
use App\Services\InstituteSourceEvidenceCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SourceEvidenceCaptureTest extends TestCase
{
 use RefreshDatabase;
 public function test_capture_creates_evidence_and_assertions_for_active_source(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
  $evidence=(new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com/about','Official institute statement','hash-1',[['subject_type'=>'institution','subject_id'=>10,'attribute'=>'name','value'=>'Example Institute','verification_state'=>'observed','confidence'=>1]]);
  $this->assertSame($source->id,$evidence->source_id);
  $this->assertSame('active',$evidence->status);
  $this->assertSame(1,Assertion::where('evidence_id',$evidence->id)->count());
 }
 public function test_capture_binds_evidence_to_matching_snapshot(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
  $snapshot=SourceSnapshot::create(['source_id'=>$source->id,'reference'=>'https://example.com','content_hash'=>'hash-snapshot','content_length'=>10,'content_type'=>'text/html','captured_at'=>now(),'status'=>'captured']);
  $evidence=(new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com','Excerpt','hash-snapshot',[], $snapshot->id);
  $this->assertSame($snapshot->id,$evidence->snapshot_id);
 }

 public function test_capture_rejects_mismatched_snapshot_hash(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
  $snapshot=SourceSnapshot::create(['source_id'=>$source->id,'reference'=>'https://example.com','content_hash'=>'hash-snapshot','content_length'=>10,'content_type'=>'text/html','captured_at'=>now(),'status'=>'captured']);
  $this->expectException(\InvalidArgumentException::class);
  (new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com','Excerpt','wrong-hash',[], $snapshot->id);
 }

 public function test_capture_rejects_incomplete_assertion_before_creating_evidence(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Official Source','url'=>'https://example.com','status'=>'active']);
  $this->expectException(\InvalidArgumentException::class);
  (new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com','Excerpt','hash-2',[['subject_type'=>'institution','attribute'=>'name','value'=>'Example Institute']]);
  $this->assertSame(0,\App\Models\Evidence::where('source_id',$source->id)->count());
 }

 public function test_capture_does_not_accept_inactive_source(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Inactive Source','url'=>'https://example.com','status'=>'inactive']);
  $this->expectException(\InvalidArgumentException::class);
  (new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com','Excerpt');
 }
}
