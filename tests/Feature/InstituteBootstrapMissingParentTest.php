<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceMonitoringPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteBootstrapMissingParentTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_parent_profile_is_reported_as_blocked_without_failing_or_registering_source(): void
    {
        $this->artisan('institutes:bootstrap-agent', ['--profile' => ['gcu-lahore-programs']])
            ->expectsOutputToContain('blocked: required parent institution "government-college-university-lahore" is missing')
            ->assertExitCode(0);

        $this->assertSame(0, Institution::query()->count());
        $this->assertSame(0, Source::query()->count());
        $this->assertSame(0, SourceMonitoringPolicy::query()->count());
    }

    public function test_profile_runs_normally_when_canonical_parent_exists(): void
    {
        Institution::create([
            'name' => 'Government College University Lahore',
            'slug' => 'government-college-university-lahore',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://gcu.edu.pk/admissions.php?pg=bs-program' => \Illuminate\Support\Facades\Http::response(
                '<html><body><a href="/programs/cs">Bachelor of Computer Science</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $this->artisan('institutes:bootstrap-agent', ['--profile' => ['gcu-lahore-programs']])
            ->assertExitCode(0);

        $this->assertSame(1, Source::query()->count());
        $this->assertSame(1, SourceMonitoringPolicy::query()->count());
    }
}
