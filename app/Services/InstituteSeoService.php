<?php

namespace App\Services;

use App\Models\Institution;

class InstituteSeoService
{
    public function institutionSchema(Institution $institution): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $institution->name,
            'url' => route('institutes.show', $institution),
        ];

        if ($institution->official_name && $institution->official_name !== $institution->name) {
            $schema['alternateName'] = $institution->official_name;
        }

        if ($institution->description) {
            $schema['description'] = $institution->description;
        }

        if ($institution->short_name && $institution->short_name !== $institution->name) {
            $schema['alternateName'] = array_values(array_unique(array_filter([
                $schema['alternateName'] ?? null,
                $institution->short_name,
            ])));
        }

        $locations = $institution->campuses
            ->filter(fn ($campus) => $campus->status === 'active')
            ->map(function ($campus) {
                $location = [
                    '@type' => 'Place',
                    'name' => $campus->name,
                ];

                if ($campus->address) {
                    $location['address'] = [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $campus->address,
                        'postalCode' => $campus->postal_code,
                    ];
                }

                if ($campus->latitude !== null && $campus->longitude !== null) {
                    $location['geo'] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => (float) $campus->latitude,
                        'longitude' => (float) $campus->longitude,
                    ];
                }

                return $location;
            })
            ->values()
            ->all();

        if ($locations !== []) {
            $schema['location'] = count($locations) === 1 ? $locations[0] : $locations;
        }

        return $schema;
    }

    public function breadcrumb(string $name, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Institutes',
                    'item' => url('/institutes/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $name,
                    'item' => $url,
                ],
            ],
        ];
    }
}
