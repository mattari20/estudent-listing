<?php
namespace App\Services;
use App\Models\MonitoringAlert;
use App\Models\SourceChangeObservation;
class MonitoringExportService {
 public function alertsCsv(): string { $out=fopen('php://temp','r+'); fputcsv($out,['id','type','severity','status','message','first_detected_at','last_detected_at']); MonitoringAlert::orderBy('id')->chunk(500,function($rows)use($out){foreach($rows as $r)fputcsv($out,[$r->id,$r->alert_type,$r->severity,$r->status,$r->message,$r->first_detected_at,$r->last_detected_at]);}); rewind($out); return stream_get_contents($out); }
 public function changesCsv(): string { $out=fopen('php://temp','r+'); fputcsv($out,['id','source_id','change_state','materiality','status','observed_at','summary']); SourceChangeObservation::orderBy('id')->chunk(500,function($rows)use($out){foreach($rows as $r)fputcsv($out,[$r->id,$r->source_id,$r->change_state,$r->materiality,$r->status,$r->observed_at,$r->summary]);}); rewind($out); return stream_get_contents($out); }
}