<?php

namespace Tests\Feature;

use Tests\TestCase;

class NationalInstituteSourceRegistryTest extends TestCase
{
    public function test_registry_covers_all_pakistan_jurisdictions_and_major_institution_categories(): void
    {
        $registry = require config_path('institute_national_source_registry.php');

        $this->assertSame([
            'punjab',
            'sindh',
            'khyber_pakhtunkhwa',
            'balochistan',
            'islamabad_capital_territory',
            'gilgit_baltistan',
            'azad_jammu_and_kashmir',
        ], array_keys($registry['jurisdictions']));

        foreach ([
            'public_university',
            'private_university',
            'degree_awarding_institution',
            'constituent_college',
            'affiliated_college',
            'independent_college',
            'school',
            'technical_institute',
            'vocational_institute',
            'dae_provider',
            'professional_institute',
            'campus',
            'sub_campus',
            'department',
            'program',
        ] as $category) {
            $this->assertContains($category, $registry['institution_categories']);
        }

        foreach ([
            'hec_recognized_campuses',
            'navttc_partner_institutes',
            'ibcc_board_directory',
            'hec_recognized_affiliated_colleges',
            'ibcc_qualification_awarding_bodies',
            'federal_education_authorities',
            'punjab_tevta',
            'sindh_tevta',
            'khyber_pakhtunkhwa_tevta',
            'balochistan_tevta',
            'ajk_tevta',
            'gilgit_baltistan_tevta',
        ] as $sourceId) {
            $this->assertArrayHasKey($sourceId, $registry['sources']);
            $source = $registry['sources'][$sourceId];
            $this->assertStringStartsWith('https://', $source['source_url']);
            $this->assertNotEmpty($source['authority']);
            $this->assertNotEmpty($source['categories']);
            $this->assertNotEmpty($source['jurisdictions']);
            $this->assertSame('needs_live_validation', $source['validation_status']);
            $this->assertSame('manual_review_required', $source['activation']);
        }
    }

    public function test_registry_keeps_unverified_sources_inactive_and_http_errors_are_not_absence_proof(): void
    {
        $registry = require config_path('institute_national_source_registry.php');

        $this->assertFalse($registry['safety']['automatic_canonical_creation']);
        $this->assertFalse($registry['safety']['automatic_approval']);
        $this->assertFalse($registry['safety']['automatic_publication']);
        $this->assertFalse($registry['safety']['http_403_means_nonexistent']);
        $this->assertFalse($registry['safety']['unverified_source_profiles_enabled']);
        $this->assertTrue($registry['safety']['identity_conflicts_require_private_review']);
        $this->assertSame('not_yet_validated', $registry['coverage_gaps']['school_level_directory_by_province']);
        $this->assertSame('not_yet_measured', $registry['coverage_gaps']['national_source_retrieval_and_parser_health']);

        $this->assertContains(
            'gilgit_baltistan',
            $registry['sources']['hec_recognized_campuses']['jurisdictions']
        );
        $this->assertContains(
            'azad_jammu_and_kashmir',
            $registry['sources']['hec_recognized_campuses']['jurisdictions']
        );
        $this->assertStringContainsString(
            'must be separately verified',
            $registry['sources']['navttc_partner_institutes']['coverage_note']
        );
    }
}
