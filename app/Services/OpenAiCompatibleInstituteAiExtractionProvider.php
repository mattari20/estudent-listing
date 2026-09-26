<?php

namespace App\Services;

use App\Contracts\InstituteAiExtractionProvider;
use Illuminate\Support\Facades\Http;

class OpenAiCompatibleInstituteAiExtractionProvider implements InstituteAiExtractionProvider
{
    public function extract(string $sourceContent, array $context = []): array
    {
        $apiKey = (string) config('institute_ai.api_key');

        if ($apiKey === '') {
            throw new \RuntimeException('Institute AI extraction is enabled but INSTITUTE_AI_API_KEY is not configured.');
        }

        $content = mb_substr($sourceContent, 0, (int) config('institute_ai.max_source_chars', 120000));

        $system = 'You are the structured data extraction layer for an education institute directory. '
            .'Extract only programme facts explicitly supported by the supplied source content. '
            .'Do not invent programmes, names, awards, disciplines, statuses, URLs, dates, fees, or other facts. '
            .'Return JSON only with this shape: {"candidates":[{"name":"string","program_level":"undergraduate|graduate|postgraduate|phd|diploma|certificate|other","award":"string|null","discipline":"string|null","status":"active|inactive|unknown","confidence":0.0,"evidence_excerpt":"short exact source excerpt"}]}. '
            .'Only include programmes clearly offered by the institution. Confidence must be between 0 and 1. '
            .'The evidence_excerpt must be short and copied from the supplied source.';

        $user = "Institution context:\n"
            .json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ."\n\nSource content:\n".$content;

        $response = Http::timeout((int) config('institute_ai.timeout', 45))
            ->connectTimeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->post((string) config('institute_ai.endpoint'), [
                'model' => config('institute_ai.model'),
                'temperature' => 0,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Institute AI provider returned HTTP '.$response->status().'.');
        }

        $raw = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($raw) || trim($raw) === '') {
            throw new \RuntimeException('Institute AI provider returned no structured content.');
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || ! isset($decoded['candidates']) || ! is_array($decoded['candidates'])) {
            throw new \RuntimeException('Institute AI provider returned an invalid candidate payload.');
        }

        return $decoded['candidates'];
    }
}
