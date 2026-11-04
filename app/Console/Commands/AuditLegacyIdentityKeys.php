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

        try {
            $connection = DB::connection($connectionName);
            $connectionConfig = $connection->getConfig();
        } catch (Throwable) {
            return false;
        }

        if (! is_array($connectionConfig)
            || (isset($connectionConfig['url']) && $connectionConfig['url'] !== '')
            || ! empty($connectionConfig['read'])
            || ! empty($connectionConfig['write'])) {
            return false;
        }

        $driver = strtolower((string) ($connectionConfig['driver'] ?? ''));
        $database = $connectionConfig['database'] ?? null;

        if (! is_string($database) || $database === '') {
            return false;
        }

        if ($driver === 'sqlite') {
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
            $isLocalHost = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
            $isTestDatabase = preg_match('/(?:^|[_-])(?:test|testing)(?:[_-]|$)/i', $database) === 1;

            return $isLocalHost && $isTestDatabase;
        }

        return false;
    }
}
