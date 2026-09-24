<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class InstituteNearbySearchService
{
    public const MAX_RADIUS_KM = 100;

    public const MAX_RESULTS = 100;

    public function search(
        float $latitude,
        float $longitude,
        float $radiusKm = 25,
        int $limit = 24,
    ): Collection {
        $this->validateCoordinates($latitude, $longitude);

        if ($radiusKm <= 0 || $radiusKm > self::MAX_RADIUS_KM) {
            throw new InvalidArgumentException('radius_km must be greater than 0 and no greater than 100.');
        }

        $limit = min(max($limit, 1), self::MAX_RESULTS);

        $latitudeDelta = $radiusKm / 111.045;
        $longitudeDelta = $radiusKm / max(111.045 * cos(deg2rad($latitude)), 0.000001);

        $campuses = Campus::query()
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('institution', fn ($query) => $query->where('status', 'active'))
            ->whereHas('geography', fn ($query) => $query->where('status', 'active'))
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
            ->with(['institution', 'geography'])
            ->get();

        return $campuses
            ->map(function (Campus $campus) use ($latitude, $longitude): array {
                return [
                    'institution' => $campus->institution,
                    'campus' => $campus,
                    'distance_km' => $this->distanceKm(
                        $latitude,
                        $longitude,
                        (float) $campus->latitude,
                        (float) $campus->longitude,
                    ),
                ];
            })
            ->filter(fn (array $item) => $item['distance_km'] <= $radiusKm)
            ->sortBy(fn (array $item) => $item['distance_km'])
            ->unique(fn (array $item) => $item['institution']->getKey())
            ->take($limit)
            ->values();
    }

    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('latitude must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('longitude must be between -180 and 180.');
        }
    }

    private function distanceKm(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2,
    ): float {
        $earthRadiusKm = 6371.0088;
        $latitudeDelta = deg2rad($latitude2 - $latitude1);
        $longitudeDelta = deg2rad($longitude2 - $longitude1);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitude1))
            * cos(deg2rad($latitude2))
            * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusKm * 2 * asin(min(1, sqrt($a)));
    }
}
