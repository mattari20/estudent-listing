<?php
namespace App\Services;
use App\Models\Source;
class SourceMonitoringPipeline {
 public function run(Source $source): array {
  $run=app(SourceRetrievalService::class)->retrieve($source);
  if($run->status!=='completed' || !$run->snapshot_id) return ['run'=>$run,'observation'=>null];
  $observation=app(SourceChangeObservationService::class)->compare($run->snapshot);
  return ['run'=>$run,'observation'=>$observation];
 }
}