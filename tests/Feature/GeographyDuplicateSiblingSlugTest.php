<?php

namespace Tests\Feature;

use App\Models\Geography;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyDuplicateSiblingSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_duplicate_sibling_slug_identity(): void
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

        $this->expectException(QueryException::class);

        Geography::create([
            'name' => 'Second Synthetic Province',
            'slug' => 'shared-province',
            'type' => 'province',
            'parent_id' => $country->id,
            'status' => 'active',
        ]);
    }
}
