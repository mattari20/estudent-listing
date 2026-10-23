<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DataGapActiveProgramOfferingCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_gap_report_counts_active_programs_without_active_offerings(): void
    {
        $institution = Institution::create([
            'name' => 'Data Gap Count University',
            'slug' => 'data-gap-count-university',
            'status' => 'active',
        ]);

        $withoutOffering = Program::create([
            'institution_id' => $institution->id,
            'name' => 'No Offering Program',
            'slug' => 'no-offering-program',
            'status' => 'active',
        ]);
        $withInactiveOffering = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Offering Program',
            'slug' => 'inactive-offering-program',
            'status' => 'active',
        ]);
        $withActiveOffering = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Active Offering Program',
            'slug' => 'active-offering-program',
            'status' => 'active',
        ]);
        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Inactive Program Without Offering',
            'slug' => 'inactive-program-without-offering',
            'status' => 'inactive',
        ]);

        ProgramOffering::create([
            'program_id' => $withInactiveOffering->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'inactive',
            'availability' => 'unknown',
        ]);
        ProgramOffering::create([
            'program_id' => $withActiveOffering->id,
            'campus_id' => null,
            'delivery_mode' => 'online',
            'status' => 'active',
            'availability' => 'unknown',
        ]);

        Artisan::call('institutes:data-gap-report', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $report['sections']['coverage']['programs_without_offerings']);
        $this->assertDatabaseHas('programs', ['id' => $withoutOffering->id]);
    }
}
