<?php
namespace Tests\Feature;
use App\Models\Geography;
use App\Services\GeographyIntegrityService;
use App\Services\PakistanGeographySeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PakistanGeographyTest extends TestCase {
 use RefreshDatabase;
 public function test_top_level_seed_is_idempotent(): void { $s=new PakistanGeographySeedService; $s->seedTopLevel(); $s->seedTopLevel(); $this->assertSame(1,Geography::where('type','country')->where('slug','pakistan')->count()); $this->assertSame(7,Geography::whereIn('type',['province','territory'])->count()); }
 public function test_integrity_rejects_self_parent(): void { $g=Geography::create(['name'=>'Bad','slug'=>'bad','type'=>'city','status'=>'active']); $g->parent_id=$g->id; $g->save(); $this->assertFalse((new GeographyIntegrityService)->validate()['valid']); }
}