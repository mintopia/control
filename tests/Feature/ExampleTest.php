<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
// use App\Models\User;
// use Laravel\Sanctum\Sanctum;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // $user = User::factory()->create();

        // Routes are protected by auth:sanctum — use Sanctum helper to authenticate in tests
        // Sanctum::actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
