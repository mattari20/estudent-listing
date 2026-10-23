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
}
