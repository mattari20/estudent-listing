<?php
namespace App\Http\Controllers;
use App\Models\Source;
use App\Services\InstituteSourceEvidenceCaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class InstituteSourceEvidenceCaptureController
{
    public function store(Request $request, Source $source, InstituteSourceEvidenceCaptureService $service): JsonResponse
    {
        $data=$request->validate([
            'reference'=>['required','string','max:2048'],
            'excerpt'=>['required','string','max:10000'],
            'content_hash'=>['nullable','string','max:128'],
            'assertions'=>['array'],
            'assertions.*.subject_type'=>['nullable','string','max:255'],
            'assertions.*.subject_id'=>['nullable','integer'],
            'assertions.*.attribute'=>['nullable','string','max:255'],
            'assertions.*.value'=>['nullable','string'],
            'assertions.*.verification_state'=>['nullable','string','max:50'],
            'assertions.*.confidence'=>['nullable','numeric','between:0,1'],
            'assertions.*.valid_from'=>['nullable','date'],
            'assertions.*.valid_until'=>['nullable','date'],
            'assertions.*.verified_at'=>['nullable','date'],
        ]);
        if($source->status!=='active'){
            return response()->json(['message'=>'Only active sources may receive evidence.'],422);
        }
        $evidence=$service->capture($source,$data['reference'],$data['excerpt'],$data['content_hash']??null,$data['assertions']??[]);
        return response()->json(['id'=>$evidence->id,'source_id'=>$source->id,'status'=>$evidence->status,'captured_at'=>$evidence->observed_at,'assertions_count'=>$evidence->assertions()->count()],201);
    }
}
