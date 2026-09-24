<?php
namespace App\Services;
use App\Models\SourceChangeObservation;
class SourceMaterialityClassifier {
 public function classify(SourceChangeObservation $observation,string $materiality,string $reason): SourceChangeObservation {
  if(!in_array($materiality,['none','low','medium','high','unknown'],true)) throw new \InvalidArgumentException('Invalid materiality.');
  $observation->update(['materiality'=>$materiality,'summary'=>($observation->summary??'').' '.$reason]);
  return $observation;
 }
}