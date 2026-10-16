<?php

namespace App\Services;

use App\Models\Geography;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PakistanGeographySeedService
{
    public const TOP_LEVEL = [
        ['name' => 'Pakistan', 'slug' => 'pakistan', 'type' => 'country', 'code' => 'PK'],
        ['name' => 'Punjab', 'slug' => 'punjab', 'type' => 'province', 'code' => 'PK-PB'],
        ['name' => 'Sindh', 'slug' => 'sindh', 'type' => 'province', 'code' => 'PK-SD'],
        ['name' => 'Khyber Pakhtunkhwa', 'slug' => 'khyber-pakhtunkhwa', 'type' => 'province', 'code' => 'PK-KP'],
        ['name' => 'Balochistan', 'slug' => 'balochistan', 'type' => 'province', 'code' => 'PK-BA'],
        ['name' => 'Islamabad Capital Territory', 'slug' => 'islamabad-capital-territory', 'type' => 'territory', 'code' => 'PK-IS'],
        ['name' => 'Gilgit-Baltistan', 'slug' => 'gilgit-baltistan', 'type' => 'territory', 'code' => 'PK-GB'],
        ['name' => 'Azad Jammu and Kashmir', 'slug' => 'azad-jammu-and-kashmir', 'type' => 'territory', 'code' => 'PK-AJ'],
    ];

    /**
     * Create missing top-level geography records without modifying existing records.
     *
     * Any duplicate identity or mismatch stops the transaction before writes occur.
     */
    public function seedTopLevel(): int
    {
        return DB::transaction(function (): int {
            $existingByIdentity = [];

            foreach (self::TOP_LEVEL as $row) {
                $matches = Geography::query()
                    ->where('slug', $row['slug'])
                    ->where('type', $row['type'])
                    ->lockForUpdate()
                    ->get();

                if ($matches->count() > 1) {
                    throw new RuntimeException(
                        "Geography seed stopped: duplicate identity {$row['type']}:{$row['slug']}."
                    );
                }

                $existingByIdentity[$row['type'] . ':' . $row['slug']] = $matches->first();
            }

            $countryKey = 'country:pakistan';
            $country = $existingByIdentity[$countryKey];
            $countryId = $country?->id;

            foreach (self::TOP_LEVEL as $index => $row) {
                $key = $row['type'] . ':' . $row['slug'];
                $existing = $existingByIdentity[$key];
                $expectedParentId = $index === 0 ? null : $countryId;

                if ($existing === null) {
                    if ($index !== 0 && $countryId === null) {
                        continue;
                    }

                    continue;
                }

                $expected = [
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'status' => 'active',
                    'parent_id' => $expectedParentId,
                ];

                $actual = [
                    'name' => $existing->name,
                    'code' => $existing->code,
                    'status' => $existing->status,
                    'parent_id' => $existing->parent_id,
                ];

                foreach ($expected as $field => $value) {
                    if ($actual[$field] != $value) {
                        throw new RuntimeException(
                            "Geography seed stopped: existing {$key} differs at {$field}; no existing records were changed."
                        );
                    }
                }
            }

            $created = 0;
            $country = $existingByIdentity[$countryKey];

            foreach (self::TOP_LEVEL as $index => $row) {
                $key = $row['type'] . ':' . $row['slug'];

                if ($existingByIdentity[$key] !== null) {
                    continue;
                }

                if ($index === 0) {
                    $country = Geography::query()->create(array_merge($row, [
                        'parent_id' => null,
                        'status' => 'active',
                    ]));
                } else {
                    if ($country === null) {
                        throw new RuntimeException('Geography seed stopped: country record is unavailable.');
                    }

                    Geography::query()->create(array_merge($row, [
                        'parent_id' => $country->id,
                        'status' => 'active',
                    ]));
                }

                $created++;
            }

            return $created;
        });
    }
}
