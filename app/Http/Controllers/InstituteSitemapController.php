<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Response;

class InstituteSitemapController
{
    public function __invoke(): Response
    {
        $urls = Institution::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['slug', 'updated_at'])
            ->map(fn (Institution $institution) => [
                'loc' => route('institutes.show', $institution),
                'lastmod' => $institution->updated_at?->toAtomString(),
            ]);

        $xml = view('institutes.sitemap', compact('urls'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
