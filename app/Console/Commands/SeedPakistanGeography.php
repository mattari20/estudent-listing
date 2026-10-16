<?php

namespace App\Console\Commands;

use App\Services\PakistanGeographySeedService;
use Illuminate\Console\Command;
use Throwable;

class SeedPakistanGeography extends Command
{
    protected $signature = 'institutes:seed-pakistan-geography';

    protected $description = 'Create missing Pakistan country and first-level geography records without modifying existing records.';

    public function handle(PakistanGeographySeedService $service): int
    {
        try {
            $count = $service->seedTopLevel();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Created {$count} Pakistan geography records; existing records were not modified.");

        return self::SUCCESS;
    }
}
