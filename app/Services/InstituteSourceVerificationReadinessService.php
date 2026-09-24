<?php
namespace App\Services;
use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\SourceMonitoringPolicy;
use App\Models\SourceRetrievalRun;
class InstituteSourceVerificationReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $source=$batch->source;
        $evidenceQuery=Evidence::query()->where('source_id',$batch->source_id);
        $evidenceTotal=(clone $evidenceQuery)->count();
        $activeEvidence=(clone $evidenceQuery)->where('status','active')->count();
        $fresh=0; $aging=0; $stale=0; $unknown=0;
        foreach((clone $evidenceQuery)->get() as $evidence){
            $state=app(SourceFreshnessService::class)->state($evidence);
            if($state==='fresh')$fresh++; elseif($state==='aging')$aging++; elseif($state==='stale')$stale++; else $unknown++;
        }
        $policy=SourceMonitoringPolicy::query()->where('source_id',$batch->source_id)->where('status','active')->first();
        $latestRun=SourceRetrievalRun::query()->where('source_id',$batch->source_id)->latest('created_at')->first();
        $latestSnapshotId=$latestRun?->snapshot_id;
        $blocking=[
            'source_missing'=>$source?0:1,
            'source_inactive'=>$source && $source->status==='active'?0:1,
            'active_evidence_missing'=>$activeEvidence>0?0:1,
            'fresh_evidence_missing'=>$fresh>0?0:1,
            'monitoring_policy_missing'=>$policy?0:1,
            'successful_retrieval_missing'=>$latestRun && $latestRun->status==='completed' && $latestSnapshotId?0:1,
            'retrieval_lineage_missing'=>$latestRun && $latestRun->snapshot_id && $latestRun->content_hash?0:1,
        ];
        return [
            'scope'=>'read_only_source_verification_readiness',
            'source'=>['id'=>$source?->id,'name'=>$source?->name,'status'=>$source?->status],
            'evidence'=>['total'=>$evidenceTotal,'active'=>$activeEvidence,'fresh'=>$fresh,'aging'=>$aging,'stale'=>$stale,'unknown'=>$unknown],
            'monitoring'=>['policy_present'=>(bool)$policy,'latest_retrieval_status'=>$latestRun?->status,'latest_retrieval_at'=>$latestRun?->completed_at],
            'blocking_findings'=>$blocking,
            'blocking_total'=>array_sum($blocking),
            'source_verification_ready'=>array_sum($blocking)===0,
            'mutation_performed'=>false,
        ];
    }
}
