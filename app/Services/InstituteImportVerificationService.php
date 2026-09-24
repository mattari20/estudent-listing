<?php
namespace App\Services;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
class InstituteImportVerificationService
{
    public function summarize(ImportBatch $batch): array
    {
        $rows = $batch->rows();
        $candidateRows=(clone $rows)->where('status','candidate')->count();
        $invalidRows=(clone $rows)->where('status','invalid')->count();
        $promotedRows=(clone $rows)->where('status','promoted')->count();
        $rejectedRows=(clone $rows)->where('status','rejected')->count();
        $sourceActive=$batch->source?->status==='active';
        $batchValidated=$batch->status==='validated';
        $candidatesWithoutApprovedIdentity=(clone $rows)->where('status','candidate')
            ->whereNotExists(function($query){$query->selectRaw('1')->from('identity_resolution_decisions')
                ->whereColumn('identity_resolution_decisions.import_row_id','import_rows.id')
                ->whereIn('identity_resolution_decisions.decision',['approved_new','approved_match']);})->count();
        $approvedMatchesMissingCanonical=ImportRow::query()->where('import_batch_id',$batch->id)->where('status','candidate')
            ->whereExists(function($query){$query->selectRaw('1')->from('identity_resolution_decisions')
                ->whereColumn('identity_resolution_decisions.import_row_id','import_rows.id')->where('decision','approved_match');})
            ->where(function($query){$query->whereNull('canonical_subject_id')->orWhere('canonical_subject_type','!=',\App\Models\Institution::class);})->count();
        $candidateRowsWithOpenConflicts=ImportConflict::query()->where('import_batch_id',$batch->id)->where('status','open')
            ->whereHas('importRow',fn($query)=>$query->where('status','candidate'))->distinct('import_row_id')->count('import_row_id');
        $blockingFindings=[
            'source_not_active'=>$sourceActive?0:1,
            'batch_not_validated'=>$batchValidated?0:1,
            'invalid_rows_present'=>$invalidRows,
            'candidates_without_approved_identity'=>$candidatesWithoutApprovedIdentity,
            'approved_matches_missing_canonical'=>$approvedMatchesMissingCanonical,
            'candidate_rows_with_open_conflicts'=>$candidateRowsWithOpenConflicts,
        ];
        $blockingTotal=array_sum($blockingFindings);
        return [
            'scope'=>'read_only_import_verification',
            'batch'=>['id'=>$batch->id,'batch_key'=>$batch->batch_key,'entity_type'=>$batch->entity_type,'status'=>$batch->status,'source_id'=>$batch->source_id,'source_status'=>$batch->source?->status],
            'population'=>['total_rows'=>(clone $rows)->count(),'candidate_rows'=>$candidateRows,'invalid_rows'=>$invalidRows,'promoted_rows'=>$promotedRows,'rejected_rows'=>$rejectedRows],
            'verification'=>['source_active'=>$sourceActive,'batch_validated'=>$batchValidated,'eligible_candidate_rows'=>max(0,$candidateRows-$candidatesWithoutApprovedIdentity-$approvedMatchesMissingCanonical-$candidateRowsWithOpenConflicts)],
            'blocking_findings'=>$blockingFindings,
            'blocking_total'=>$blockingTotal,
            'verification_ready'=>$blockingTotal===0 && $candidateRows>0,
            'mutation_performed'=>false,
        ];
    }
}
