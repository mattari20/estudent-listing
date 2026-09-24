<?php
namespace App\Services;
use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\Source;
class InstituteSourceEvidenceCaptureService
{
    public function capture(Source $source, string $reference, string $excerpt, ?string $contentHash = null, array $assertions = []): Evidence
    {
        $evidence=Evidence::create([
            'source_id'=>$source->id,
            'reference'=>$reference,
            'excerpt'=>$excerpt,
            'content_hash'=>$contentHash,
            'observed_at'=>now(),
            'status'=>'active',
        ]);
        foreach($assertions as $assertion){
            Assertion::create([
                'evidence_id'=>$evidence->id,
                'subject_type'=>$assertion['subject_type'] ?? null,
                'subject_id'=>$assertion['subject_id'] ?? null,
                'attribute'=>$assertion['attribute'] ?? null,
                'value'=>$assertion['value'] ?? null,
                'verification_state'=>$assertion['verification_state'] ?? 'observed',
                'confidence'=>$assertion['confidence'] ?? null,
                'valid_from'=>$assertion['valid_from'] ?? null,
                'valid_until'=>$assertion['valid_until'] ?? null,
                'verified_at'=>$assertion['verified_at'] ?? null,
            ]);
        }
        return $evidence->refresh();
    }
}
