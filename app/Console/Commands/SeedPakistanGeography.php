<?php
namespace App\Console\Commands;
use App\Services\PakistanGeographySeedService;
use Illuminate\Console\Command;
class SeedPakistanGeography extends Command {
 protected $signature='institutes:seed-pakistan-geography';
 protected $description='Seed the governed Pakistan country and first-level administrative geography records.';
 public function handle(PakistanGeographySeedService $service): int { $count=$service->seedTopLevel(); $this->info("Seeded {$count} Pakistan top-level geography records."); return self::SUCCESS; }
}