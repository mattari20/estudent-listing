<?php
namespace Tests\Feature;
use App\Models\MonitoringAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MonitoringOperatorConsoleTest extends TestCase {
 use RefreshDatabase;
 public function test_alert_can_be_acknowledged_by_authenticated_operator(): void {
  $user=\App\Models\User::create(['name'=>'Operator','email'=>'operator@example.test','password'=>bcrypt('password'),'role'=>'operator']);
  $alert=app(\App\Services\MonitoringAlertService::class)->raise('test','warning','Test');
  $this->actingAs($user)->post(route('institutes.operations.monitoring.alerts.acknowledge',$alert))->assertRedirect();
  $this->assertSame('acknowledged',$alert->refresh()->status);
 }
}