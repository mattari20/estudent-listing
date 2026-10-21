<?php

namespace Tests\Unit;

use App\Services\InstituteImportControllerSupport;
use PHPUnit\Framework\TestCase;

class InstituteImportEvidenceValidationTest extends TestCase
{
    public function test_valid_official_source_evidence_is_accepted(): void
    {
        $errors = (new InstituteImportControllerSupport())->validateRow([
            'name' => 'Example University',
            'slug' => 'example-university',
            'official_source_url' => 'https://example.edu.pk/about',
            'source_excerpt' => 'Example University is a public sector university established under its charter.',
        ], []);

        $this->assertSame([], $errors);
    }

    public function test_evidence_url_and_excerpt_must_be_supplied_together(): void
    {
        $errors = (new InstituteImportControllerSupport())->validateRow([
            'name' => 'Example University',
            'slug' => 'example-university',
            'official_source_url' => 'https://example.edu.pk/about',
            'source_excerpt' => '',
        ], []);

        $this->assertContains('official_source_url and source_excerpt must be supplied together', $errors);
    }

    public function test_evidence_requires_https_and_a_minimum_excerpt_length(): void
    {
        $errors = (new InstituteImportControllerSupport())->validateRow([
            'name' => 'Example University',
            'slug' => 'example-university',
            'official_source_url' => 'http://example.edu.pk/about',
            'source_excerpt' => 'Too short',
        ], []);

        $this->assertContains('official_source_url must be a valid HTTPS URL', $errors);
        $this->assertContains('source_excerpt must contain at least 20 characters copied from the official source', $errors);
    }

    public function test_import_without_evidence_fields_remains_supported(): void
    {
        $errors = (new InstituteImportControllerSupport())->validateRow([
            'name' => 'Example University',
            'slug' => 'example-university',
        ], []);

        $this->assertSame([], $errors);
    }
}
