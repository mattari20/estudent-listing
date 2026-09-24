<?php
namespace App\Http\Controllers;
use App\Services\MonitoringExportService;
class MonitoringExportController {
 public function alerts(MonitoringExportService $service){return response($service->alertsCsv(),200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="monitoring-alerts.csv"']);}
 public function changes(MonitoringExportService $service){return response($service->changesCsv(),200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="monitoring-changes.csv"']);}
}