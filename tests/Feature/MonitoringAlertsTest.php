<?php
namespace Tests\Feature;
use App\Models\MonitoringAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MonitoringAlertsTest extends TestCase {
 use RefreshDatabase;
 public function test_open_alerts_are_deduplicated(): void {
  $a=app(\App\Services\MonitoringAlertService::class)->raise('retrieval_failure','warning','Failure');
  $b=app(\App\Services\MonitoringAlertService::class)->raise('retrieval_failure','warning','Failure again');
  $this->assertSame($a->id,$b->id); $this->assertSame(1,MonitoringAlert::count());
 }
 public function test_alert_can_be_acknowledged_and_resolved(): void {
  $a=app(\App\Services\MonitoringAlertService::class)->raise('test','info','Test');
  app(\App\Services\MonitoringAlertStateService::class)->acknowledge($a); $this->assertSame('acknowledged',$a->refresh()->status);
  app(\App\Services\MonitoringAlertStateService::class)->resolve($a); $this->assertSame('resolved',$a->refresh()->status);
 }


 public function test_resolved_alert_is_not_reused_for_a_new_occurrence(): void {
  $service=app(\App\Services\MonitoringAlertService::class);
  $state=app(\App\Services\MonitoringAlertStateService::class);
  $first=$service->raise('resolved-recurrence','warning','First occurrence');
  $state->resolve($first);
  $second=$service->raise('resolved-recurrence','warning','Second occurrence');
  $this->assertNotSame($first->id,$second->id);
  $this->assertSame('resolved',$first->fresh()->status);
  $this->assertSame('open',$second->fresh()->status);
  $this->assertSame(2,MonitoringAlert::where('alert_type','resolved-recurrence')->count());
 }
}