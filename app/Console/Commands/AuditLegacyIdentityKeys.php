<?php

namespace App\Console\Commands;

use App\Services\LegacyIdentityKeyInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditLegacyIdentityKeys extends Command
{
    protected $signature = 'institutes:audit-legacy-identity-keys {--limit=100 : Maximum NULL-key IDs to sample per table}';

    protected $description = 'Read-only inventory of legacy NULL idempotency keys in an isolated test database';

    public function handle(LegacyIdentityKeyInventoryService $inventory): int
    {
        if (! app()->environment('testing')) {
            $this->error('This inventory command is restricted to APP_ENV=testing.');
            return self::FAILURE;
        }

        if (! $this->usesAnExplicitlyIsolatedTestDatabase()) {
            $this->error('Refusing inventory: the active database connection is not an explicitly isolated test database.');
            return self::FAILURE;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 1000) {
            $this->error('The --limit option must be an integer between 1 and 1000.');
            return self::FAILURE;
        }

        $report = $inventory->report($limit);
        $this->table(
            ['Table', 'Key column', 'Status', 'Total', 'Keyed', 'Legacy NULL', 'Sample IDs'],
            array_map(fn (array $row): array => [
                $row['table'],
                $row['column'],
                $row['status'],
                $row['total'],
                $row['keyed'],
                $row['legacy_null_key'],
                implode(',', $row['null_id_sample']),
            ], $report['tables'])
        );
        $this->line(json_encode($report['totals'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->info('Read-only inventory complete. No keys, identities, decisions, candidate states or history were changed.');

        return self::SUCCESS;
    }

    /**
     * Validate the resolved Laravel connection configuration without opening a PDO
     * connection or issuing a query. APP_ENV alone is not a database safety boundary.
     */
    private function usesAnExplicitlyIsolatedTestDatabase(): bool
    {
        $connectionName = config('database.default');
        if (! is_string($connectionName) || $connectionName === '') {
            return false;
        }

        $connections = config('database.connections');
        $configuredConnection = is_array($connections)
            ? ($connections[$connectionName] ?? null)
            : null;

        if (! is_array($configuredConnection)
            || (isset($configuredConnection['url']) && $configuredConnection['url'] !== '')
            || array_key_exists('read', $configuredConnection)
            || array_key_exists('write', $configuredConnection)) {
            return false;
        }

        try {
            $connection = DB::connection($connectionName);
            $connectionConfig = $connection->getConfig();
        } catch (Throwable) {
            return false;
        }

        if (! is_array($connectionConfig)
            || (isset($connectionConfig['url']) && $connectionConfig['url'] !== '')
            || array_key_exists('read', $connectionConfig)
            || array_key_exists('write', $connectionConfig)) {
            return false;
        }

        $driver = strtolower((string) ($connectionConfig['driver'] ?? ''));
        $database = $connectionConfig['database'] ?? null;

        if (! is_string($database) || $database === '') {
            return false;
        }

        if ($driver === 'sqlite') {
            if ($connectionName !== 'sqlite') {
                return false;
            }

            if ($database === ':memory:') {
                return true;
            }

            $expected = database_path('testing.sqlite');
            $actual = str_starts_with($database, DIRECTORY_SEPARATOR)
                ? $database
                : base_path($database);

            $expectedPath = realpath($expected) ?: $expected;
            $actualPath = realpath($actual) ?: $actual;

            return $actualPath === $expectedPath;
        }

        if ($driver === 'mysql') {
            $host = strtolower(trim((string) ($connectionConfig['host'] ?? '')));
            $username = (string) ($connectionConfig['username'] ?? '');
            $port = (int) ($connectionConfig['port'] ?? 0);
            $isLocalHost = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);

            // MySQL is accepted only for this repository's isolated GitHub Actions
            // service database; local forwarding or arbitrary *_test schemas are not.
            return $connectionName === 'mysql'
                && getenv('GITHUB_ACTIONS') === 'true'
                && $isLocalHost
                && $port === 3306
                && $database === 'approval_migration_test'
                && $username === 'approval_ci';
        }

        return false;
    }
}
