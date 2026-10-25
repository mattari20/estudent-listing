<?php

namespace Tests\Unit;

use App\Services\StructuredEnrichmentPayloadValidator;
use PHPUnit\Framework\TestCase;

class StructuredEnrichmentPayloadValidatorTest extends TestCase
{
    private function validator(): StructuredEnrichmentPayloadValidator
    {
        return new StructuredEnrichmentPayloadValidator();
    }

    public function test_valid_campus_payload_has_no_blockers(): void
    {
        $this->assertSame([], $this->validator()->blockers('campus', [
            'institution_id' => 2,
            'name' => 'Main Campus',
            'slug' => 'main-campus',
        ]));
    }

    public function test_missing_required_fields_are_reported_deterministically(): void
    {
        $this->assertSame(
            ['institution_id:required', 'name:required', 'slug:required'],
            $this->validator()->blockers('program', [])
        );
    }

    public function test_invalid_relationship_ids_are_blocked(): void
    {
        $this->assertSame(
            ['program_id:must_be_positive_id', 'campus_id:must_be_positive_id'],
            $this->validator()->blockers('offering', [
                'program_id' => 0,
                'campus_id' => 'unknown',
                'delivery_mode' => 'on-campus',
            ])
        );
    }

    public function test_unsupported_entity_type_is_blocked(): void
    {
        $this->assertSame(
            ['unsupported_entity_type'],
            $this->validator()->blockers('institution', ['name' => 'Example'])
        );
    }

    public function test_whitespace_names_and_slugs_are_blocked(): void
    {
        $this->assertSame(
            ['name:must_be_non_empty_string', 'slug:must_be_non_empty_string'],
            $this->validator()->blockers('campus', [
                'institution_id' => 2,
                'name' => '   ',
                'slug' => " \t ",
            ])
        );
    }
}
