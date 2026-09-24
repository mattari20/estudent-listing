<?php
namespace App\Services;
use App\Models\Source;
class SourceMonitoringPipeline {
 public function run(Source $source): array {
  $run=app(SourceRetrievalService::class)->retrieve($source);
  if($run->status!=='completed' || !$run->snapshot_id) return ['run'=>$run,'observation'=>null,'intelligence'=>null];
  $observation=app(SourceChangeObservationService::class)->compare($run->snapshot);
  $intelligence=app(SourceChangeIntelligenceService::class)->process($observation);
  return ['run'=>$run,'observation'=>$observation,'intelligence'=>$intelligence];
 }
}