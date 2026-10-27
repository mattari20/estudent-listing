<?php

namespace App\Services;

use Illuminate\Support\Str;

class InstituteDiscoveryCandidateExtractor
{
    /** Bump when deterministic extraction rules change. */
    public const VERSION = '5';

    public function extract(string $entityType, string $html, array $identity = []): array
    {
        return match ($entityType) {
            'program' => $this->programs($html),
            'campus' => $this->campuses($html),
            'institution' => $this->institution($html, $identity),
            default => throw new \InvalidArgumentException("Unsupported autonomous discovery entity type: {$entityType}"),
        };
    }

    /**
     * Create a top-level identity candidate only when the official source page
     * contains a configured institution identity term. Configured values alone
     * are never enough: the page must corroborate an identity term and the row
     * remains non-canonical until the existing identity-review process approves it.
     */
    private function institution(string $html, array $identity): array
    {
        foreach (['name', 'official_name', 'short_name', 'slug', 'institution_type'] as $field) {
            if (! is_string($identity[$field] ?? null) || trim($identity[$field]) === '') {
                return [];
            }
        }

        $terms = array_values(array_filter(
            $identity['identity_terms'] ?? [],
            fn ($term): bool => is_string($term) && trim($term) !== ''
        ));
        if ($terms === []) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $parts = [$this->normalize($dom->textContent ?? '')];

        foreach ($xpath->query('//title | //meta[@property="og:site_name"] | //meta[@name="application-name"]') as $node) {
            $value = $node->nodeName === 'meta'
                ? $node->getAttribute('content')
                : ($node->textContent ?? '');
            if ($value !== '') {
                $parts[] = $this->normalize($value);
            }
        }

        $visibleText = $this->normalize(implode(' ', array_filter($parts)));
        $matchedTerm = null;
        $matchedOffset = false;
        foreach ($terms as $term) {
            $offset = mb_stripos($visibleText, trim($term));
            if ($offset !== false) {
                $matchedTerm = trim($term);
                $matchedOffset = $offset;
                break;
            }
        }
        libxml_clear_errors();

        if ($matchedTerm === null || $matchedOffset === false) {
            return [];
        }

        $excerpt = trim(mb_substr($visibleText, max(0, $matchedOffset - 90), 260));

        return [[
            'name' => trim($identity['name']),
            'official_name' => trim($identity['official_name']),
            'short_name' => trim($identity['short_name']),
            'slug' => trim($identity['slug']),
            'institution_type' => trim($identity['institution_type']),
            'status' => 'candidate',
            'confidence' => 0.9800,
            'evidence_excerpt' => $excerpt !== '' ? $excerpt : $matchedTerm,
        ]];
    }

    private function programs(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $programs = [];
        $sectionFound = false;

        // Restrict extraction to the named academic-programme section and stop
        // at the next heading of equal or higher rank.
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $headingText = mb_strtolower($this->normalize($heading->textContent ?? ''));
            if (! in_array($headingText, ['academic programs', 'academic programmes', 'programs offered', 'programmes offered'], true)) {
                continue;
            }

            $sectionFound = true;
            $headingLevel = (int) substr(strtolower($heading->nodeName), 1);
            foreach ($xpath->query('following::*', $heading) as $node) {
                $nodeName = strtolower($node->nodeName);
                if (preg_match('/^h[1-6]$/', $nodeName)) {
                    $nodeLevel = (int) substr($nodeName, 1);
                    if ($nodeLevel <= $headingLevel) {
                        break;
                    }
                }
                if (! in_array($nodeName, ['a', 'h3', 'h4', 'h5', 'h6'], true)) {
                    continue;
                }

                $name = $this->normalize($node->textContent ?? '');
                if ($this->looksLikeDiscipline($name)) {
                    $this->addProgram($programs, $name, 0.90);
                }
            }
        }

        // Fallback only for official pages without a named programme section.
        // If semantic main-content markup exists, never scan the whole page:
        // navigation/footer degree-looking links are not programme evidence.
        if (! $sectionFound) {
            $contentLinks = $xpath->query('//main//a | //article//a | //*[@role="main"]//a');
            $hasMainContent = $contentLinks !== false && $contentLinks->length > 0;
            $links = $hasMainContent ? $contentLinks : $xpath->query('//a');

            foreach ($links as $link) {
                if (! $hasMainContent && $xpath->query('ancestor::nav|ancestor::header|ancestor::footer|ancestor::*[@role="navigation" or @role="contentinfo"]', $link)->length > 0) {
                    continue;
                }

                $name = $this->normalize($link->textContent ?? '');
                if (preg_match('/^(Bachelor|BS\b|BSc\b|B\.Sc\b)/i', $name)
                    && stripos($name, 'Visit Department') === false) {
                    $this->addProgram($programs, $name, 0.95);
                }
            }
        }

        libxml_clear_errors();
        return array_values($programs);
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function looksLikeDiscipline(string $name): bool
    {
        if ($name === '' || mb_strlen($name) > 100
            || preg_match('/^(Bachelor|Master|MPhil|PhD)\b/i', $name)) {
            return false;
        }
        if (preg_match('/^(home|about us|admissions?|apply now|contact us|facult(y|ies)|departments?|visit department|read more|learn more|programs offered|academic programs|academic programmes|download|details|more|campus life|events)$/i', $name)) {
            return false;
        }
        return preg_match('/^[\p{L}\p{N}][\p{L}\p{N} &(),.\/-]{1,98}$/u', $name) === 1;
    }

    private function addProgram(array &$programs, string $name, float $confidence): void
    {
        $name = preg_replace('/^Bachelor of Science\(BS\)/i', 'Bachelor of Science (BS)', $name);
        $key = Str::lower(Str::slug($name));
        if ($key === '') {
            return;
        }

        $award = stripos($name, 'Bachelor of Science') === 0 || preg_match('/^BS\b/i', $name)
            ? 'BS'
            : (preg_match('/^BSc\b|^B\.Sc\b/i', $name) ? 'BSc' : 'Bachelor');
        $discipline = $name;
        if (preg_match('/\bin\s+(.+)$/i', $name, $match)) {
            $discipline = trim($match[1]);
        } elseif (preg_match('/^Bachelor of\s+(.+)$/i', $name, $match)) {
            $discipline = trim($match[1]);
        } elseif (preg_match('/^BS\s*(?:in\s+)?(.+)$/i', $name, $match)) {
            $discipline = trim($match[1]);
        }

        $programs[$key] = [
            'name' => $name,
            'award' => $award,
            'discipline' => $discipline,
            'program_level' => 'undergraduate',
            'status' => 'active',
            'confidence' => $confidence,
            'evidence_excerpt' => $name,
        ];
    }

    private function campuses(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $campuses = [];

        foreach ($xpath->query('//a|//h1|//h2|//h3|//h4') as $node) {
            $name = $this->normalize($node->textContent ?? '');
            if ($name === '' || ! preg_match('/\bCampus\b/i', $name)
                || preg_match('/\b(Campus Life|Campus Facilities|Campus Map|Campus News)\b/i', $name)) {
                continue;
            }
            $campuses[Str::lower(Str::slug($name))] = [
                'name' => $name, 'campus_type' => 'campus', 'status' => 'active',
                'confidence' => 0.9000, 'evidence_excerpt' => $name,
            ];
        }
        libxml_clear_errors();
        return array_values($campuses);
    }
}
