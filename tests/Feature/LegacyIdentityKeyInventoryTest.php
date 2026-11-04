<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\LegacyIdentityKeyInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyIdentityKeyInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_classifies_legacy_null_keys_without_mutating_identity_or_history(): void
    {
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Legacy identity inventory source',
            'url' => 'https://example.test/legacy-identity-inventory',
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'legacy-identity-inventory-batch',
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 1,
            'valid_rows' => 1,
            'candidate_rows' => 1,
            'received_at' => now(),
            'completed_at' => now(),
        ]);

        $row = ImportRow::create([
            'import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'candidate',
            'match_state' => 'unresolved',
            'payload' => ['name' => 'Legacy candidate'],
        ]);

        $now = now();
        DB::table('identity_match_candidates')->insert([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'match_type' => 'legacy',
            'confidence' => null,
            'status' => 'unresolved',
            'reason' => 'fixture',
            'matched_at' => null,
            'identity_key' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('identity_resolution_decisions')->insert([
            'import_row_id' => $row->id,
            'institution_id' => null,
            'decision' => 'deferred',
            'actor_type' => 'system',
            'actor_id' => null,
            'reason' => 'fixture',
            'decided_at' => $now,
            'decision_identity_key' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('import_conflicts')->insert([
            'import_row_id' => $row->id,
            'import_batch_id' => $batch->id,
            'conflict_type' => 'legacy',
            'severity' => 'info',
            'status' => 'open',
            'description' => 'fixture conflict',
            'identity_key' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('evidence')->insert([
            'source_id' => $source->id,
            'snapshot_id' => null,
            'reference' => $source->url,
            'excerpt' => 'fixture',
            'content_hash' => hash('sha256', 'fixture'),
            'observed_at' => $now,
            'status' => 'active',
            'discovery_identity_key' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('assertions')->insert([
            'evidence_id' => null,
            'subject_type' => 'institution',
            'subject_id' => 1,
            'attribute' => 'name',
            'value' => 'fixture',
            'verification_state' => 'discovered',
            'discovery_identity_key' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $report = app(LegacyIdentityKeyInventoryService::class)->report(10);

        $this->assertFalse($report['mutations_performed']);
        $this->assertSame(7, count($report['tables']));
        $this->assertSame(7, $report['totals']['legacy_null_key']);
        $this->assertSame(0, $report['totals']['keyed']);

        $byTable = collect($report['tables'])->keyBy('table');
        foreach ([
            'identity_match_candidates',
            'identity_resolution_decisions',
            'import_conflicts',
            'import_batches',
            'evidence',
            'import_rows',
            'assertions',
        ] as $table) {
            $this->assertSame(1, $byTable[$table]['legacy_null_key']);
            $this->assertSame('legacy_null_keys_present', $byTable[$table]['status']);
        }

        $this->assertDatabaseHas('identity_match_candidates', ['import_row_id' => $row->id, 'identity_key' => null]);
        $this->assertDatabaseHas('identity_resolution_decisions', ['import_row_id' => $row->id, 'decision_identity_key' => null]);
        $this->assertDatabaseHas('import_conflicts', ['import_row_id' => $row->id, 'identity_key' => null]);
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'discovery_identity_key' => null]);
        $this->assertDatabaseHas('import_rows', ['id' => $row->id, 'discovery_identity_key' => null]);
    }
    public function test_command_runs_against_the_explicitly_configured_isolated_test_database(): void
    {
        $this->artisan('institutes:audit-legacy-identity-keys --limit=5')
            ->assertExitCode(0)
            ->expectsOutputToContain('Read-only inventory complete.');
    }

    public function test_command_refuses_a_production_mysql_host_before_inventory_queries(): void
    {
        $this->assertCommandRefusesDatabaseConfiguration([
            'driver' => 'mysql',
            'host' => 'db.production.example',
            'port' => 3306,
            'database' => 'estudent',
            'username' => 'production_user',
            'password' => 'not-used',
            'url' => null,
        ]);
    }

    public function test_command_refuses_a_non_test_database_even_on_localhost(): void
    {
        $this->assertCommandRefusesDatabaseConfiguration([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'estudent',
            'username' => 'local_user',
            'password' => 'not-used',
            'url' => null,
        ]);
    }

    public function test_command_refuses_database_url_overrides_even_when_other_fields_look_safe(): void
    {
        $this->assertCommandRefusesDatabaseConfiguration([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'approval_migration_test',
            'username' => 'test_user',
            'password' => 'not-used',
            'url' => 'mysql://user:password@remote-db.example/estudent',
        ]);
    }

    public function test_command_refuses_an_unrecognized_connection_name(): void
    {
        config(['database.default' => 'unrecognized_connection']);
        DB::purge('unrecognized_connection');

        $inventory = \\Mockery::mock(LegacyIdentityKeyInventoryService::class);
        $inventory->shouldNotReceive('report');
        $this->app->instance(LegacyIdentityKeyInventoryService::class, $inventory);

        $this->artisan('institutes:audit-legacy-identity-keys')
            ->assertExitCode(1);
    }

    private function assertCommandRefusesDatabaseConfiguration(array $connection): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => $connection,
        ]);
        DB::purge('mysql');

        $inventory = \\Mockery::mock(LegacyIdentityKeyInventoryService::class);
        $inventory->shouldNotReceive('report');
        $this->app->instance(LegacyIdentityKeyInventoryService::class, $inventory);

        $this->artisan('institutes:audit-legacy-identity-keys')
            ->assertExitCode(1);
    }

}
