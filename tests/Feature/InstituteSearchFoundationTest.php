<?php
namespace Tests\Feature;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstituteSearchFoundationTest extends TestCase { use RefreshDatabase; public function test_search_is_active_only(): void { Institution::create(['name'=>'Visible','slug'=>'visible','status'=>'active']); Institution::create(['name'=>'Hidden','slug'=>'hidden','status'=>'inactive']); $this->get(route('institutes.search',['q'=>'Visible']))->assertOk()->assertSee('Visible')->assertDontSee('Hidden'); } public function test_empty_search_is_valid(): void { $this->get(route('institutes.search'))->assertOk(); } }