<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutionImportReviewPackTest extends TestCase
{
    private function candidateCsv(): string
    {
        return base_path('data/imports/2026-10-review-pack/institution-candidates-v1.0.csv');
    }

    public function test_review_pack_has_current_institution_import_schema(): void
    {
        $this->assertFileExists($this->candidateCsv());
        $handle = fopen($this->candidateCsv(), 'r');
        $this->assertIsResource($handle);

        try {
            $headers = fgetcsv($handle);
            $this->assertSame(
                ['name', 'official_name', 'short_name', 'slug', 'institution_type', 'status'],
                array_map(static fn ($value) => trim((string) $value), $headers ?: []),
            );
        } finally {
            fclose($handle);
        }
    }

    public function test_review_pack_has_ten_named_candidates_and_unique_slugs(): void
    {
        $handle = fopen($this->candidateCsv(), 'r');
        $this->assertIsResource($handle);

        try {
            $headers = fgetcsv($handle);
            $rows = [];
            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }
                $rows[] = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), null));
            }
        } finally {
            fclose($handle);
        }

        $this->assertCount(10, $rows, 'The reviewed import pack should contain exactly ten candidate rows.');

        $slugs = [];
        foreach ($rows as $index => $row) {
            $this->assertNotSame('', trim((string) ($row['name'] ?? '')), 'Candidate row '.($index + 1).' must have a name.');
            $slug = trim((string) ($row['slug'] ?? ''));
            $this->assertNotSame('', $slug, 'Candidate row '.($index + 1).' must have a slug.');
            $this->assertNotContains($slug, $slugs, 'Candidate slugs must be unique within the review pack.');
            $slugs[] = $slug;

            // These fields intentionally remain blank until reviewed; never infer a classification/status.
            $this->assertSame('', trim((string) ($row['institution_type'] ?? '')));
            $this->assertSame('', trim((string) ($row['status'] ?? '')));
        }
    }
}
