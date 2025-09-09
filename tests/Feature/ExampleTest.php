<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// use App\Models\User;
// use Laravel\Sanctum\Sanctum;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function testTheApplicationReturnsASuccessfulResponse(): void
    {
        $user = User::factory()->create();

        // Ensure user won't be redirected on first login by middleware
        $user->first_login = false;
        $user->save();

        // Routes are protected by auth:sanctum — authenticate the test user
        Sanctum::actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
