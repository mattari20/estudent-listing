<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutionCleanImportCsvTest extends TestCase
{
    public function test_clean_import_file_has_nine_rows_and_unique_slugs(): void
    {
        $path = base_path('data/imports/2026-10-review-pack/institution-candidates-clean-v1.0.csv');
        $this->assertFileExists($path);
        $handle = fopen($path, 'r');
        $this->assertIsResource($handle);

        try {
            $headers = fgetcsv($handle);
            $this->assertSame(
                ['name', 'official_name', 'short_name', 'slug', 'institution_type', 'status'],
                array_map(static fn ($value) => trim((string) $value), $headers ?: []),
            );
            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [] || $values === [null]) {
                    continue;
                }
                $rows[] = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null));
            }
        } finally {
            fclose($handle);
        }

        $this->assertCount(9, $rows);
        $slugs = array_column($rows, 'slug');
        $this->assertCount(count(array_unique($slugs)), $slugs);
        $names = array_column($rows, 'name');
        $this->assertNotContains('COMSATS University Islamabad', $names);
        foreach ($rows as $row) {
            $this->assertNotSame('', trim((string) $row['name']));
            $this->assertSame('', trim((string) ($row['institution_type'] ?? '')));
            $this->assertSame('', trim((string) ($row['status'] ?? '')));
        }
    }
}
