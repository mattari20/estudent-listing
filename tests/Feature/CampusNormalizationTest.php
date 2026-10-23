<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampusNormalizationTest extends TestCase
{
    public function test_campus_name_is_trimmed_and_slug_is_generated_from_trimmed_name(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('campus', [
            'institution_id' => 42,
            'name' => '  Main Campus  ',
        ]);

        $this->assertSame('Main Campus', $normalized['name']);
        $this->assertSame(Str::slug('Main Campus'), $normalized['slug']);
    }
}
