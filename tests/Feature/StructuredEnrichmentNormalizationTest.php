<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StructuredEnrichmentNormalizationTest extends TestCase
{
    public function test_program_name_is_trimmed_and_missing_slug_is_generated_from_normalized_name(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('program', [
            'institution_id' => 42,
            'name' => '  BS Computer Science  ',
        ]);

        $this->assertSame('BS Computer Science', $normalized['name']);
        $this->assertSame(Str::slug('BS Computer Science'), $normalized['slug']);
    }

    public function test_explicit_program_slug_is_preserved_after_name_normalization(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('program', [
            'institution_id' => 42,
            'name' => '  BS Computer Science  ',
            'slug' => 'official-program-slug',
        ]);

        $this->assertSame('BS Computer Science', $normalized['name']);
        $this->assertSame('official-program-slug', $normalized['slug']);
    }
}
