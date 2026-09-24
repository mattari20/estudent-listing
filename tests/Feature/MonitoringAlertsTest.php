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
}