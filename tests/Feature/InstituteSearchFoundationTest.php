<?php
namespace Tests\Feature;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InstituteSearchFoundationTest extends TestCase { use RefreshDatabase; public function test_search_is_active_only(): void { Institution::create(['name'=>'Visible','slug'=>'visible','status'=>'active']); Institution::create(['name'=>'Hidden','slug'=>'hidden','status'=>'inactive']); $this->get(route('institutes.search',['q'=>'Visible']))->assertOk()->assertSee('Visible')->assertDontSee('Hidden'); } public function test_empty_search_is_valid(): void { $this->get(route('institutes.search'))->assertOk(); } 
    public function test_unknown_search_term_returns_valid_empty_results_page(): void
    {
        $this->get(route('institutes.search', ['q' => 'no-such-institute-xyz']))
            ->assertOk()
            ->assertSee('no-such-institute-xyz');
    }
}