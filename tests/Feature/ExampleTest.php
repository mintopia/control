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
        $user = \App\Models\User::factory()->create();

        // Ensure user won't be redirected on first login by middleware
        $user->first_login = false;
        $user->save();

        // Routes are protected by auth:sanctum — authenticate the test user
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
