<?php
namespace Tests\Feature;
use App\Models\Assertion;
use App\Models\Source;
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
 public function test_capture_does_not_accept_inactive_source(): void
 {
  $source=Source::create(['source_type'=>'official','name'=>'Inactive Source','url'=>'https://example.com','status'=>'inactive']);
  $this->expectException(\Throwable::class);
  (new InstituteSourceEvidenceCaptureService)->capture($source,'https://example.com','Excerpt');
 }
}
