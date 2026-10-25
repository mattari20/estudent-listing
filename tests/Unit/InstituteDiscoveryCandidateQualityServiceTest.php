<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryCandidateQualityService;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryCandidateQualityServiceTest extends TestCase
{
    public function test_valid_candidate_passes_deterministic_quality_gates(): void
    {
        $blockers = (new InstituteDiscoveryCandidateQualityService())->blockers(
            'program',
            ['name' => 'BS Computer Science', 'confidence' => 0.95, 'evidence_excerpt' => 'Programme listing'],
            'https://university.example.edu/programmes',
            false
        );

        $this->assertSame([], $blockers);
    }

    public function test_invalid_name_confidence_and_source_are_blocked_with_stable_codes(): void
    {
        $blockers = (new InstituteDiscoveryCandidateQualityService())->blockers(
            'program',
            ['name' => '   ', 'confidence' => 1.5],
            'javascript:alert(1)',
            false
        );

        $this->assertContains('candidate_name_missing_or_invalid', $blockers);
        $this->assertContains('candidate_slug_empty', $blockers);
        $this->assertContains('source_url_invalid', $blockers);
        $this->assertContains('confidence_out_of_range', $blockers);
    }

    public function test_ai_candidate_without_evidence_excerpt_is_blocked(): void
    {
        $blockers = (new InstituteDiscoveryCandidateQualityService())->blockers(
            'program',
            ['name' => 'BS Computer Science', 'confidence' => 0.95],
            'https://university.example.edu/programmes',
            true
        );

        $this->assertContains('ai_candidate_missing_evidence_excerpt', $blockers);
    }

    public function test_unsupported_entity_type_is_blocked(): void
    {
        $blockers = (new InstituteDiscoveryCandidateQualityService())->blockers(
            'institution',
            ['name' => 'Test University'],
            'https://university.example.edu',
            false
        );

        $this->assertContains('unsupported_entity_type', $blockers);
    }
    public function test_source_url_with_embedded_credentials_is_blocked(): void
    {
        $blockers = (new InstituteDiscoveryCandidateQualityService())->blockers(
            'program',
            ['name' => 'BS Computer Science', 'confidence' => 0.95],
            'https://operator:secret@university.example.edu/programmes',
            false
        );

        $this->assertContains('source_url_invalid', $blockers);
    }

}
