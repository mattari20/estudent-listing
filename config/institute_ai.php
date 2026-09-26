<?php

return [
    'enabled' => (bool) env('INSTITUTE_AI_ENABLED', false),
    'provider' => env('INSTITUTE_AI_PROVIDER', 'openai_compatible'),
    'endpoint' => env('INSTITUTE_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    'api_key' => env('INSTITUTE_AI_API_KEY'),
    'model' => env('INSTITUTE_AI_MODEL', 'gpt-5.6-luna'),
    'timeout' => (int) env('INSTITUTE_AI_TIMEOUT', 45),
    'max_source_chars' => (int) env('INSTITUTE_AI_MAX_SOURCE_CHARS', 120000),
    'fallback_to_deterministic' => (bool) env('INSTITUTE_AI_FALLBACK_DETERMINISTIC', false),
];
