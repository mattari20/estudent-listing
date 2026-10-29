<?php

namespace Tests\Feature;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutesDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutes_route_exists(): void
    {
        $this->get('/institutes')->assertOk();
    }

    public function test_directory_paginates_active_institutions_and_shows_navigation(): void
    {
        foreach (range(1, 30) as $number) {
            Institution::query()->create([
                'name' => sprintf('AAA Pagination Test Institution %02d', $number),
                'slug' => sprintf('aaa-pagination-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        Institution::query()->create([
            'name' => 'AAA Inactive Pagination Test Institution',
            'slug' => 'aaa-inactive-pagination-test-institution',
            'status' => 'candidate',
        ]);

        $this->get('/institutes?per_page=9')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('directory-pagination', false)
            ->assertSee('directory-per-page', false)
            ->assertSee('AAA Pagination Test Institution 01')
            ->assertDontSee('AAA Pagination Test Institution 30')
            ->assertDontSee('AAA Inactive Pagination Test Institution');

        $this->get('/institutes?per_page=9&page=2')
            ->assertOk()
            ->assertSee('AAA Pagination Test Institution 10')
            ->assertDontSee('AAA Pagination Test Institution 01');
    }

    public function test_directory_page_size_is_limited_to_supported_values_and_query_is_preserved(): void
    {
        foreach (range(1, 30) as $number) {
            Institution::query()->create([
                'name' => sprintf('AAA Page Size Test Institution %02d', $number),
                'slug' => sprintf('aaa-page-size-test-institution-%02d', $number),
                'status' => 'active',
            ]);
        }

        $this->get('/institutes?per_page=24')
            ->assertOk()
            ->assertSee('Showing')
            ->assertSee('AAA Page Size Test Institution 01')
            ->assertDontSee('AAA Page Size Test Institution 30');

        $this->get('/institutes?per_page=999')
            ->assertOk()
            ->assertSee('24 per page')
            ->assertSee('AAA Page Size Test Institution 01')
            ->assertDontSee('AAA Page Size Test Institution 30');

        $this->get('/institutes?per_page=9&page=2')
            ->assertOk()
            ->assertSee('AAA Page Size Test Institution 10')
            ->assertDontSee('AAA Page Size Test Institution 01')
            ->assertSee('per_page=9');
    }

    public function test_readonly_diagnostic_contract_has_prerequisite_foreign_key_metadata_fields(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/hostinger-readonly-diagnostics.yml'));
        $this->assertIsString($workflow);

        $this->assertStringContainsString("'check' => 'institution_field_approvals_schema_readiness'", $workflow);
        $this->assertStringContainsString("'mutation_performed' => false", $workflow);
        $this->assertStringContainsString('\'foreign_key_prerequisites\' => $prerequisites', $workflow);
        $this->assertStringContainsString('\'prerequisite_blockers\' => $prerequisiteBlockers', $workflow);
        $this->assertStringContainsString('\'metadata_introspection_succeeded\' => !$metadataError', $workflow);

        foreach ([
            'institutions',
            'sources',
            'source_change_observations',
            'source_snapshots',
            'evidence',
            'users',
            'official_source_refresh_events',
        ] as $table) {
            $this->assertStringContainsString("'".$table."' =>", $workflow);
        }

        $this->assertStringContainsString('storage_engine_foreign_key_compatible', $workflow);
        $this->assertStringContainsString('referenced_id_type_not_compatible_with_unsigned_bigint_foreignId', $workflow);
        $this->assertStringContainsString('referenced_id_not_primary_or_unique', $workflow);
        $this->assertStringContainsString('TABLE_SCHEMA = DATABASE()', $workflow);
    }

}
