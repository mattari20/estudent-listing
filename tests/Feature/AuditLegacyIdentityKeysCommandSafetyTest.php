<?php

namespace Tests\Feature;

use App\Services\LegacyIdentityKeyInventoryService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AuditLegacyIdentityKeysCommandSafetyTest extends TestCase
{
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
            'username' => 'approval_ci',
            'password' => 'not-used',
            'url' => 'mysql://user:password@remote-db.example/estudent',
        ]);
    }

    public function test_command_refuses_an_unrecognized_connection_name(): void
    {
        config(['database.default' => 'unrecognized_connection']);
        DB::purge('unrecognized_connection');

        $inventory = Mockery::mock(LegacyIdentityKeyInventoryService::class);
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

        $inventory = Mockery::mock(LegacyIdentityKeyInventoryService::class);
        $inventory->shouldNotReceive('report');
        $this->app->instance(LegacyIdentityKeyInventoryService::class, $inventory);

        $this->artisan('institutes:audit-legacy-identity-keys')
            ->assertExitCode(1);
    }
}
