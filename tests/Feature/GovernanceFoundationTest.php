<?php
namespace Tests\Feature;
use App\Models\ModerationAction;
use App\Models\ReleaseRecord;
use App\Models\SecurityAuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class GovernanceFoundationTest extends TestCase {
 use RefreshDatabase;
 public function test_security_audit_event_can_be_recorded(): void {$e=SecurityAuditEvent::create(['event_type'=>'security.review','severity'=>'info']);$this->assertDatabaseHas('security_audit_events',['id'=>$e->id]);}
 public function test_moderation_action_can_be_recorded(): void {$a=ModerationAction::create(['target_type'=>'review','target_id'=>1,'action'=>'flag']);$this->assertDatabaseHas('moderation_actions',['id'=>$a->id]);}
 public function test_release_record_is_versioned(): void {$r=ReleaseRecord::create(['version'=>'0.1.0','environment'=>'staging','status'=>'planned']);$this->assertSame('0.1.0',$r->version);}
}