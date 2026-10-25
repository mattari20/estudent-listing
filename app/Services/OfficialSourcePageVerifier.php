<?php

namespace App\\Services;

use Illuminate\\Http\\Client\\Response;
use Illuminate\\Support\\Facades\\File;
use Throwable;

class OfficialSourcePageVerifier
{
    private const MAX_ROWS = 100;
    private const MAX_UNIQUE_URLS = 100;
    private const MAX_BODY_BYTES = 5_242_880;

    public function verify(string $registerPath): array
    {
        $report = [
            'check' => 'institutes_official_source_page_verification',
            'checked_at' => now()->toIso8601String(),
            'mode' => 'external_read_only_no_database',
            'database_mutations' => 0,
            'mutation_performed' => false,
            'source_ownership_verified' => false,
            'register_path' => $registerPath,
            'summary' => [],
            'sources' => [],
            'records' => [],
            'errors' => [],
            'limitations' => [
                'Reachability and matching text do not by themselves prove that a domain is controlled by the institution.',
                'A phrase match is a review signal, not proof of current admission availability or campus delivery.',
                'PDF, image, JavaScript-rendered, and other non-text sources may require manual review.',
                'This command performs no database writes and does not promote, approve, or publish records.',
            ],
        ];

        if (! is_file($registerPath) || ! is_readable($registerPath)) {
            $report['errors'][] = 'register_file_missing_or_unreadable';
            return $report;
        }

        $handle = fopen($registerPath, 'rb');
        if ($handle === false) {
            $report['errors'][] = 'register_file_unreadable';
            return $report;
        }

        $required = [
            'program_id', 'institution_id', 'program_name', 'official_source_url',
            'source_status', 'campus_status', 'remediation_decision',
            'candidate_campus_id', 'candidate_campus_name',
            'candidate_delivery_mode', 'campus_source_url',
        ];
        $header = fgetcsv($handle);
        if (! is_array($header) || array_diff($required, $header) !== []) {
            fclose($handle);
            $report['errors'][] = 'register_required_columns_missing';
            return $report;
        }

        $indexes = array_flip($header);
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                $report['errors'][] = 'register_row_limit_exceeded';
                return $report;
            }

            $row = [];
            foreach ($required as $field) {
                $row[$field] = trim((string) ($values[$indexes[$field]] ?? ''));
            }
            $row['_line'] = $line;
            $rows[] = $row;
        }
        fclose($handle);

        $urlRows = [];
        foreach ($rows as $row) {
            foreach ([
                'official_programme' => $row['official_source_url'],
                'campus' => $row['campus_source_url'],
            ] as $role => $url) {
                if ($url === '') {
                    continue;
                }
                $urlRows[$url][] = [
                    'register_line' => $row['_line'],
                    'program_id' => $row['program_id'],
                    'program_name' => $row['program_name'],
                    'campus_name' => $row['candidate_campus_name'],
                    'role' => $role,
                ];
            }
        }

        if (count($urlRows) > self::MAX_UNIQUE_URLS) {
            $report['errors'][] = 'unique_source_url_limit_exceeded';
            $report['summary'] = ['register_rows' => count($rows), 'unique_source_urls' => count($urlRows)];
            return $report;
        }

        $sourceResults = [];
        foreach ($urlRows as $url => $references) {
            $sourceResults[$url] = $this->verifyUrl($url, $references);
        }

        $summary = [
            'register_rows' => count($rows),
            'unique_source_urls' => count($urlRows),
            'reachable_http_sources' => 0,
            'http_or_retrieval_failures' => 0,
            'missing_programme_urls' => 0,
            'missing_campus_urls' => 0,
            'programme_name_found_in_source' => 0,
            'programme_name_not_found_in_source' => 0,
            'campus_name_found_in_source' => 0,
            'campus_name_not_found_in_source' => 0,
            'non_text_sources' => 0,
        ];

        foreach ($sourceResults as $result) {
            if (($result['retrieval_status'] ?? '') === 'reachable_text') {
                $summary['reachable_http_sources']++;
            } elseif (($result['retrieval_status'] ?? '') === 'non_text_content') {
                $summary['non_text_sources']++;
                $summary['reachable_http_sources']++;
            } else {
                $summary['http_or_retrieval_failures']++;
            }
        }

        foreach ($rows as $row) {
            $programUrl = $row['official_source_url'];
            $campusUrl = $row['campus_source_url'];
            $programSource = $programUrl !== '' ? ($sourceResults[$programUrl] ?? null) : null;
            $campusSource = $campusUrl !== '' ? ($sourceResults[$campusUrl] ?? null) : null;

            $programNameFound = $programSource !== null
                && ($programSource['retrieval_status'] ?? '') === 'reachable_text'
                && $this->containsPhrase((string) ($programSource['normalized_text'] ?? ''), $row['program_name']);
            $campusNameFound = $campusSource !== null
                && ($campusSource['retrieval_status'] ?? '') === 'reachable_text'
                && $this->containsPhrase((string) ($campusSource['normalized_text'] ?? ''), $row['candidate_campus_name']);

            if ($programUrl === '') {
                $summary['missing_programme_urls']++;
            } elseif ($programNameFound) {
                $summary['programme_name_found_in_source']++;
            } else {
                $summary['programme_name_not_found_in_source']++;
            }

            if ($campusUrl === '') {
                $summary['missing_campus_urls']++;
            } elseif ($campusNameFound) {
                $summary['campus_name_found_in_source']++;
            } else {
                $summary['campus_name_not_found_in_source']++;
            }

            $report['records'][] = [
                'register_line' => $row['_line'],
                'program_id' => $row['program_id'] !== '' ? (int) $row['program_id'] : null,
                'institution_id' => $row['institution_id'] !== '' ? (int) $row['institution_id'] : null,
                'program_name' => $row['program_name'],
                'candidate_campus_name' => $row['candidate_campus_name'],
                'official_source_url' => $programUrl !== '' ? $programUrl : null,
                'official_source_retrieval_status' => $programSource['retrieval_status'] ?? 'missing_url',
                'official_source_http_status' => $programSource['http_status'] ?? null,
                'official_source_content_type' => $programSource['content_type'] ?? null,
                'official_source_sha256' => $programSource['sha256'] ?? null,
                'program_name_found_in_source_text' => $programUrl !== '' ? (bool) $programNameFound : null,
                'campus_source_url' => $campusUrl !== '' ? $campusUrl : null,
                'campus_source_retrieval_status' => $campusSource['retrieval_status'] ?? 'missing_url',
                'campus_source_http_status' => $campusSource['http_status'] ?? null,
                'campus_source_content_type' => $campusSource['content_type'] ?? null,
                'campus_source_sha256' => $campusSource['sha256'] ?? null,
                'campus_name_found_in_source_text' => $campusUrl !== '' ? (bool) $campusNameFound : null,
                'source_ownership_verified' => false,
                'review_required' => true,
            ];
        }

        foreach ($sourceResults as &$sourceResult) {
            unset($sourceResult['normalized_text']);
        }
        unset($sourceResult);

        $report['sources'] = array_values($sourceResults);
        $report['summary'] = $summary;
        $report['verification_status'] = $report['errors'] === []
            ? 'SOURCE_RETRIEVAL_REPORT_CREATED_REVIEW_REQUIRED'
            : 'BLOCKED_SOURCE_VERIFICATION';
        return $report;
    }

    private function verifyUrl(string $url, array $references): array
    {
        $base = [
            'url' => $url,
            'reference_count' => count($references),
            'references' => $references,
            'retrieved_at' => now()->toIso8601String(),
            'source_ownership_verified' => false,
            'http_status' => null,
            'content_type' => null,
            'sha256' => null,
            'title' => null,
            'retrieval_status' => 'retrieval_failed',
            'error_code' => null,
            'normalized_text' => '',
        ];

        try {
            $response = app(SafeSourceHttpRetriever::class)->get(
                $url,
                15,
                5,
                'eStudent-OfficialSourceVerifier/1.0'
            );
            $base['http_status'] = $response->status();
            $base['content_type'] = trim(explode(';', (string) $response->header('Content-Type'))[0]);

            if (! $response->successful()) {
                $base['retrieval_status'] = 'http_error';
                $base['error_code'] = 'http_status_'.$response->status();
                return $base;
            }

            $body = $response->body();
            if (strlen($body) > self::MAX_BODY_BYTES) {
                $base['retrieval_status'] = 'body_too_large';
                $base['error_code'] = 'body_exceeds_5mb';
                return $base;
            }

            $base['sha256'] = hash('sha256', $body);
            $contentType = strtolower((string) $base['content_type']);
            if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'text/plain') && ! str_contains($contentType, 'application/xhtml')) {
                $base['retrieval_status'] = 'non_text_content';
                $base['error_code'] = 'manual_review_required_for_content_type';
                return $base;
            }

            $title = '';
            if (preg_match('/<title[^>]*>(.*?)<\\/title>/is', $body, $matches) === 1) {
                $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $text = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalizedText = $this->normalizeText($text);
            $base['title'] = mb_substr($title, 0, 300);
            $base['normalized_text'] = $normalizedText;
            $base['retrieval_status'] = 'reachable_text';
            return $base;
        } catch (Throwable $exception) {
            $base['retrieval_status'] = 'retrieval_failed';
            $base['error_code'] = class_basename($exception);
            return $base;
        }
    }

    private function containsPhrase(string $normalizedText, string $phrase): bool
    {
        $needle = $this->normalizeText($phrase);
        return $needle !== '' && str_contains($normalizedText, $needle);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $text) ?? $text;
        return trim(preg_replace('/\\s+/u', ' ', $text) ?? $text);
    }
}
