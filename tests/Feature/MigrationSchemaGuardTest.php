<?php

namespace Tests\Feature;

use App\Support\Database\MigrationSchemaGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MigrationSchemaGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Schema::dropIfExists('migration_guard_test');
        Schema::dropIfExists('migration_guard_partial_test');

        parent::tearDown();
    }

    public function test_create_table_guard_creates_absent_table_and_accepts_complete_existing_table(): void
    {
        MigrationSchemaGuard::createTableIfMissing('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        }, ['id', 'name']);

        $this->assertTrue(Schema::hasTable('migration_guard_test'));

        MigrationSchemaGuard::createTableIfMissing('migration_guard_test', function (Blueprint $table): void {
            throw new RuntimeException('The existing table must not be recreated.');
        }, ['id', 'name']);

        $this->assertTrue(Schema::hasColumn('migration_guard_test', 'name'));
    }

    public function test_create_table_guard_rejects_existing_column_with_incompatible_type(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Exact column metadata assertion is specific to MySQL/MariaDB.');
        }

        Schema::create('migration_guard_partial_test', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 32);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incompatible type');
        MigrationSchemaGuard::createTableIfMissing(
            'migration_guard_partial_test',
            function (Blueprint $table): void {
                $table->string('name', 255);
            },
            ['id', 'name'],
            ['name' => ['type' => 'varchar(255)', 'nullable' => false]]
        );
    }

    public function test_create_table_guard_fails_closed_for_partially_created_table(): void
    {
        Schema::create('migration_guard_partial_test', function (Blueprint $table): void {
            $table->id();
        });

        try {
            MigrationSchemaGuard::createTableIfMissing('migration_guard_partial_test', function (Blueprint $table): void {
                $table->string('name');
            }, ['id', 'name']);
            $this->fail('A partially-created table must not be silently accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('missing columns: name', $exception->getMessage());
        }
    }

    public function test_nullable_unique_string_guard_is_repeatable_and_creates_expected_index(): void
    {
        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
        });

        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );

        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );

        $this->assertTrue(Schema::hasColumn('migration_guard_test', 'identity_key'));
        $this->assertTrue(Schema::hasIndex('migration_guard_test', 'migration_guard_test_identity_unique'));
    }

    public function test_nullable_unique_string_guard_does_not_accept_composite_unique_index_as_equivalent(): void
    {
        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key', 64)->nullable();
            $table->string('scope');
            $table->unique(['identity_key', 'scope'], 'migration_guard_composite_unique');
        });

        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );

        $this->assertTrue(Schema::hasIndex('migration_guard_test', 'migration_guard_test_identity_unique'));
        $this->assertTrue(Schema::hasIndex('migration_guard_test', 'migration_guard_composite_unique'));
    }

    public function test_nullable_unique_string_guard_rejects_a_prefixed_named_unique_index_on_mysql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Prefix-index metadata is specific to MySQL/MariaDB.');
        }

        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key', 64)->nullable();
        });

        DB::statement('CREATE UNIQUE INDEX migration_guard_test_identity_unique ON migration_guard_test (identity_key(32))');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incompatible definition');
        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );
    }

    public function test_nullable_unique_string_guard_rejects_incompatible_existing_column_type_on_mysql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Column metadata assertion is specific to MySQL/MariaDB.');
        }

        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key', 32)->nullable();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('incompatible metadata');
        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );
    }

    public function test_nullable_unique_unsigned_big_integer_guard_accepts_mysql_and_mariadb_metadata(): void
    {
        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_link_id')->nullable();
        });

        MigrationSchemaGuard::ensureNullableUniqueUnsignedBigInteger(
            'migration_guard_test',
            'source_link_id',
            'migration_guard_test_source_link_unique'
        );

        MigrationSchemaGuard::ensureNullableUniqueUnsignedBigInteger(
            'migration_guard_test',
            'source_link_id',
            'migration_guard_test_source_link_unique'
        );

        $this->assertTrue(Schema::hasIndex('migration_guard_test', 'migration_guard_test_source_link_unique'));
    }

    public function test_nullable_unique_string_guard_rejects_existing_duplicate_values(): void
    {
        Schema::create('migration_guard_test', function (Blueprint $table): void {
            $table->id();
            $table->string('identity_key', 64)->nullable();
        });

        DB::table('migration_guard_test')->insert([
            ['identity_key' => 'duplicate-key'],
            ['identity_key' => 'duplicate-key'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duplicate non-NULL values');
        MigrationSchemaGuard::ensureNullableUniqueString(
            'migration_guard_test',
            'identity_key',
            64,
            'migration_guard_test_identity_unique'
        );
    }
}
