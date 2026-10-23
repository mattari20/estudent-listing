<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyDuplicateSiblingSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_siblings_with_same_slug_are_reported_as_duplicate_identity(): void
    {
        $country = Geography::create([
            'name' => 'Synthetic Country',
            'slug' => 'synthetic-country',
            'type' => 'country',
            'status' => 'active',
        ]);
        Geography::create([
            'name' => 'First Synthetic Province',
            'slug' => 'shared-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);
        Geography::create([
            'name' => 'Second Synthetic Province',
            'slug' => 'shared-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);

        $result = app(GeographyIntegrityService::class)->validate();

        $this->assertFalse($result['valid']);
        $this->assertCount(2, array_filter(
            $result['errors'],
            fn (string $error): bool => str_starts_with($error, 'duplicate_parent_slug:'),
        ));
    }
}
