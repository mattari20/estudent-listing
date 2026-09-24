<?php
namespace App\Services;
use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\Source;
use App\Models\SourceSnapshot;
class InstituteSourceEvidenceCaptureService
{
    public function capture(Source $source, string $reference, string $excerpt, ?string $contentHash = null, array $assertions = [], ?int $snapshotId = null): Evidence
    {
        if ($source->status !== 'active') {
            throw new \InvalidArgumentException('Evidence can only be captured from an active source.');
        }

        if ($snapshotId !== null) {
            $snapshot = SourceSnapshot::query()->where('id', $snapshotId)->where('source_id', $source->id)->where('status', 'captured')->first();
            if (! $snapshot) {
                throw new \InvalidArgumentException('Evidence snapshot must belong to the source and be captured.');
            }
            if ($contentHash !== null && $snapshot->content_hash !== $contentHash) {
                throw new \InvalidArgumentException('Evidence content hash does not match the supplied snapshot.');
            }
        }

        foreach ($assertions as $index => $assertion) {
            foreach (['subject_type', 'subject_id', 'attribute', 'value'] as $field) {
                if (!array_key_exists($field, $assertion) || $assertion[$field] === null || $assertion[$field] === '') {
                    throw new \InvalidArgumentException('Evidence assertion #'.($index + 1).' is missing required field: '.$field.'.');
                }
            }
        }

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
