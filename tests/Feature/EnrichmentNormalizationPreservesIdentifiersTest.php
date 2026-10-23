<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Tests\TestCase;

class EnrichmentNormalizationPreservesIdentifiersTest extends TestCase
{
    public function test_numeric_parent_identifier_is_not_string_trimmed_or_rewritten(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('program', [
            'institution_id' => 42,
            'name' => 'Computer Science',
        ]);

        $this->assertSame(42, $normalized['institution_id']);
        $this->assertIsInt($normalized['institution_id']);
        $this->assertSame('Computer Science', $normalized['name']);
    }
}
