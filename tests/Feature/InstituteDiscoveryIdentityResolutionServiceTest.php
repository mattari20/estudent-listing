<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use App\Services\InstituteDiscoveryIdentityResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteDiscoveryIdentityResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_program_match_is_scoped_to_parent_institution(): void
    {
        $one = Institution::create(['name' => 'University One', 'slug' => 'university-one', 'status' => 'active']);
        $two = Institution::create(['name' => 'University Two', 'slug' => 'university-two', 'status' => 'active']);
        $program = Program::create([
            'institution_id' => $one->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);

        $service = app(InstituteDiscoveryIdentityResolutionService::class);
        $match = $service->resolve('program', $one->id, ' COMPUTER   SCIENCE ');
        $otherParent = $service->resolve('program', $two->id, 'Computer Science');

        $this->assertSame('matched', $match['state']);
        $this->assertSame($program->id, $match['canonical_id']);
        $this->assertSame('unresolved', $otherParent['state']);
    }

    public function test_unknown_candidate_is_not_fuzzy_merged(): void
    {
        $institution = Institution::create(['name' => 'Example University', 'slug' => 'example-university', 'status' => 'active']);
        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'status' => 'active',
        ]);

        $result = app(InstituteDiscoveryIdentityResolutionService::class)
            ->resolve('program', $institution->id, 'Computing Science');

        $this->assertSame('unresolved', $result['state']);
        $this->assertNull($result['canonical_id']);
        $this->assertSame('no_exact_canonical_match', $result['reason']);
    }

    public function test_empty_name_and_unsupported_entity_fail_closed(): void
    {
        $service = app(InstituteDiscoveryIdentityResolutionService::class);

        $empty = $service->resolve('program', 1, '   ');
        $unsupported = $service->resolve('institution', 1, 'Example University');

        $this->assertSame('unresolved', $empty['state']);
        $this->assertSame('unsupported', $unsupported['state']);
    }
}
