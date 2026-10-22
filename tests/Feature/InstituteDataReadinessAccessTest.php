<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteDataReadinessAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_access_data_readiness_operations(): void
    {
        $operator = User::create([
            'name' => 'Readiness Operator',
            'email' => 'readiness-operator@example.test',
            'password' => 'test-password',
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('institutes.operations.data-readiness'))
            ->assertOk();
    }
}
