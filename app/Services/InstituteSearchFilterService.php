<?php

namespace App\Services;

class InstituteSearchFilterService
{
    public function apply($query, array $filters)
    {
        return $query
            ->when($filters['type'] ?? '', fn ($builder) => $builder->where('institution_type', $filters['type']))
            ->when($filters['province'] ?? '', fn ($builder) => $builder->whereHas(
                'campuses',
                fn ($campus) => $campus
                    ->where('status', 'active')
                    ->whereHas('geography', fn ($geography) => $geography
                        ->where('status', 'active')
                        ->whereHas('parent', fn ($district) => $district
                            ->where('status', 'active')
                            ->whereHas('parent', fn ($province) => $province
                                ->where('status', 'active')
                                ->where('name', $filters['province'])
                                ->where('type', 'province')))
                    )
            ))
            ->when($filters['district'] ?? '', fn ($builder) => $builder->whereHas(
                'campuses',
                fn ($campus) => $campus
                    ->where('status', 'active')
                    ->whereHas('geography', fn ($geography) => $geography
                        ->where('status', 'active')
                        ->whereHas('parent', fn ($district) => $district
                            ->where('status', 'active')
                            ->where('name', $filters['district'])
                            ->where('type', 'district')))
            ))
            ->when($filters['city'] ?? '', fn ($builder) => $builder->whereHas(
                'campuses',
                fn ($campus) => $campus
                    ->where('status', 'active')
                    ->whereHas('geography', fn ($geography) => $geography
                        ->where('status', 'active')
                        ->where('name', $filters['city'])
                        ->where('type', 'city'))
            ))
            ->when($filters['program'] ?? '', fn ($builder) => $builder->whereHas('programs', fn ($program) => $program
                ->where('status', 'active')
                ->where(fn ($match) => $match
                    ->where('name', 'like', "%{$filters['program']}%")
                    ->orWhere('discipline', 'like', "%{$filters['program']}%"))))
            ->when($filters['program_level'] ?? '', fn ($builder) => $builder->whereHas('programs', fn ($program) => $program
                ->where('status', 'active')
                ->where('program_level', $filters['program_level'])))
            ->when($filters['delivery_mode'] ?? '', fn ($builder) => $builder->whereHas('programs.offerings', fn ($offering) => $offering
                ->where('status', 'active')
                ->where('delivery_mode', $filters['delivery_mode'])))
            ->when($filters['admission_status'] ?? '', fn ($builder) => $builder->whereHas('programs.offerings.admissionIntakes', fn ($intake) => $intake
                ->whereNotIn('status', ['cancelled', 'suspended'])
                ->where('status', $filters['admission_status'])) );
    }
}
