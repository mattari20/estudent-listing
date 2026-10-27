<?php

namespace Tests\Unit;

use App\Services\InstituteDiscoveryCandidateExtractor;
use PHPUnit\Framework\TestCase;

class InstituteDiscoveryCandidateExtractorTest extends TestCase
{
    public function test_program_extraction_stops_before_admissions_rules_and_footer_links(): void
    {
        $html = <<<'HTML'
        <html><body><main>
          <h2>Academic Programs</h2>
          <h6>Biology</h6>
          <h6>Biotechnology</h6>
          <h6>Computer Science</h6>
          <h2>General Rules and Regulations</h2>
          <h6>Sports</h6>
          <h6>Important Links</h6>
          <a href="/portal">www.gcuonline.pk</a>
          <h5>Requirements for Diploma Holders/B Tech (Pass) Degree for BSc Electrical Engineering</h5>
        </main></body></html>
        HTML;

        $programs = (new InstituteDiscoveryCandidateExtractor())->extract('program', $html);

        $this->assertSame(['Biology', 'Biotechnology', 'Computer Science'], array_column($programs, 'name'));
    }

    public function test_program_extraction_falls_back_to_explicit_degree_links_without_section_heading(): void
    {
        $html = '<html><body><a>Bachelor of Computer Science</a><a>About Us</a></body></html>';

        $programs = (new InstituteDiscoveryCandidateExtractor())->extract('program', $html);

        $this->assertSame(['Bachelor of Computer Science'], array_column($programs, 'name'));
    }

    public function test_fallback_prefers_main_content_and_ignores_degree_looking_navigation_and_footer_links(): void
    {
        $html = <<<'HTML'
        <html><body>
          <nav><a href="/bs-portal">BS Admission Portal</a><a href="/bachelor">Bachelor Admissions Guide</a></nav>
          <main>
            <h2>Undergraduate Degree Listing</h2>
            <a href="/programs/bs-computer-science">BS Computer Science</a>
            <a href="/programs/bs-psychology">Bachelor of Science (BS) in Psychology</a>
          </main>
          <footer><a href="/bs-rules">BS Rules and Regulations</a></footer>
        </body></html>
        HTML;

        $programs = (new InstituteDiscoveryCandidateExtractor())->extract('program', $html);

        $this->assertSame(
            ['BS Computer Science', 'Bachelor of Science (BS) in Psychology'],
            array_column($programs, 'name')
        );
    }

    public function test_institution_identity_is_staged_only_when_the_official_page_corroborates_the_configured_identity(): void
    {
        $identity = [
            'name' => 'Government College University Lahore',
            'official_name' => 'Government College University, Lahore',
            'short_name' => 'GCU',
            'slug' => 'government-college-university-lahore',
            'institution_type' => 'public university',
            'identity_terms' => ['Government College University Lahore', 'GC University Lahore'],
        ];
        $html = '<html><head><title>GC University Lahore</title></head><body>'
            .'<p>Government College University Lahore is a public university.</p>'
            .'</body></html>';

        $candidates = (new InstituteDiscoveryCandidateExtractor())->extract('institution', $html, $identity);

        $this->assertCount(1, $candidates);
        $this->assertSame('Government College University Lahore', $candidates[0]['name']);
        $this->assertSame('government-college-university-lahore', $candidates[0]['slug']);
        $this->assertStringContainsString('Government College University Lahore', $candidates[0]['evidence_excerpt']);

        $unrelated = '<html><body><h1>Example Technical Academy</h1><p>Other institute</p></body></html>';
        $this->assertSame([], (new InstituteDiscoveryCandidateExtractor())->extract('institution', $unrelated, $identity));
        $this->assertSame([], (new InstituteDiscoveryCandidateExtractor())->extract('institution', $html, []));
    }
}
