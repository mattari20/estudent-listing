<?php

namespace App\Services;

class DiscoveryPersistenceIdentityService
{
    public function batchKey(
        int $sourceId,
        string $profile,
        string $contentHash,
        string $sourceType,
        string $extractionMode,
        string $extractorVersion,
        ?int $supersedesBatchId = null,
    ): string {
        return hash('sha256', implode('|', [
            'discovery-batch:v1',
            $sourceId,
            $profile,
            $contentHash,
            $sourceType,
            $extractionMode,
            $extractorVersion,
            $supersedesBatchId ?? 'initial',
        ]));
    }

    public function evidenceKey(string $batchKey): string
    {
        return hash('sha256', 'discovery-evidence:v1|'.$batchKey);
    }

    public function rowKey(string $batchKey, int $rowNumber): string
    {
        return hash('sha256', 'discovery-row:v1|'.$batchKey.'|'.$rowNumber);
    }

    public function assertionKey(string $rowKey, string $attribute): string
    {
        return hash('sha256', 'discovery-assertion:v1|'.$rowKey.'|'.$attribute);
    }
}
