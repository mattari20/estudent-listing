<?php

namespace App\Contracts;

interface InstituteAiExtractionProvider
{
    public function extract(string $sourceContent, array $context = []): array;
}
