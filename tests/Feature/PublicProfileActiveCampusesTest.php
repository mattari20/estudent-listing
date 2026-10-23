<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileActiveCampusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_shows_only_active_campuses(): void
    {
        $institution = Institution::create([
            'name' => 'Campus Lifecycle University',
            'slug' => 'campus-lifecycle-university',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Open Main Campus',
            'slug' => 'open-main-campus',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Closed Historic Campus',
            'slug' => 'closed-historic-campus',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/campus-lifecycle-university')
            ->assertOk()
            ->assertSee('Open Main Campus')
            ->assertDontSee('Closed Historic Campus');
    }
}
