<?php

namespace App\Services;

use Illuminate\Support\Str;

class InstituteDiscoveryCandidateExtractor
{
    public function extract(string $entityType, string $html): array
    {
        return match ($entityType) {
            'program' => $this->programs($html),
            'campus' => $this->campuses($html),
            default => throw new \InvalidArgumentException(
                "Unsupported autonomous discovery entity type: {$entityType}"
            ),
        };
    }

    private function programs(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $programs = [];

        foreach ($xpath->query('//a') as $link) {
            $name = trim(preg_replace('/\s+/', ' ', $link->textContent ?? ''));

            if (! preg_match('/^Bachelor\b/i', $name) || stripos($name, 'Visit Department') !== false) {
                continue;
            }

            $name = preg_replace('/^Bachelor of Science\(BS\)/i', 'Bachelor of Science (BS)', $name);
            $award = stripos($name, 'Bachelor of Science') === 0 ? 'BS' : 'Bachelor';

            $discipline = $name;
            if (preg_match('/\bin\s+(.+)$/i', $name, $match)) {
                $discipline = trim($match[1]);
            } elseif (preg_match('/^Bachelor of\s+(.+)$/i', $name, $match)) {
                $discipline = trim($match[1]);
            }

            $programs[Str::lower(Str::slug($name))] = [
                'name' => $name,
                'award' => $award,
                'discipline' => $discipline,
                'program_level' => 'undergraduate',
                'status' => 'active',
                'confidence' => 0.9500,
                'evidence_excerpt' => $name,
            ];
        }

        libxml_clear_errors();

        return array_values($programs);
    }

    private function campuses(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $campuses = [];

        foreach ($xpath->query('//a|//h1|//h2|//h3|//h4') as $node) {
            $name = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));

            if (
                $name === ''
                || ! preg_match('/\bCampus\b/i', $name)
                || preg_match('/\b(Campus Life|Campus Facilities|Campus Map|Campus News)\b/i', $name)
            ) {
                continue;
            }

            $name = preg_replace('/\s+/', ' ', $name);
            $campuses[Str::lower(Str::slug($name))] = [
                'name' => $name,
                'campus_type' => 'campus',
                'status' => 'active',
                'confidence' => 0.9000,
                'evidence_excerpt' => $name,
            ];
        }

        libxml_clear_errors();

        return array_values($campuses);
    }
}
