<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchInactiveRelationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_level_filter_ignores_inactive_programs(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Inactive Program University',
            'slug' => 'inactive-program-university',
            'status' => 'active',
        ]);

        Program::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Archived Undergraduate Program',
            'slug' => 'archived-undergraduate-program',
            'program_level' => 'undergraduate',
            'status' => 'inactive',
        ]);

        $this->get('/institutes/search?program_level=undergraduate')
            ->assertOk()
            ->assertDontSee('Inactive Program University');
    }

    public function test_delivery_mode_filter_ignores_inactive_offerings(): void
    {
        $institution = Institution::query()->create([
            'name' => 'Inactive Offering University',
            'slug' => 'inactive-offering-university',
            'status' => 'active',
        ]);

        $program = Program::query()->create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'inactive-offering-computer-science',
            'program_level' => 'undergraduate',
            'status' => 'active',
        ]);

        ProgramOffering::query()->create([
            'program_id' => $program->id,
            'delivery_mode' => 'online',
            'status' => 'inactive',
            'availability' => 'open',
        ]);

        $this->get('/institutes/search?delivery_mode=online')
            ->assertOk()
            ->assertDontSee('Inactive Offering University');
    }
}
