<?php

namespace Tests\Unit\app\Models;

use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function testCanDeleteFalseWhenOnlyAccount()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => true]);
        $acc = LinkedAccount::factory()->create(['user_id' => $user->id, 'social_provider_id' => $provider->id]);
        $this->assertFalse($acc->canDelete());
    }

    public function testCanDeleteTrueWhenProviderNotAuth()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => false]);
        // add another account so there are >1
        LinkedAccount::factory()->create(['user_id' => $user->id, 'social_provider_id' => $provider->id]);
        $acc = LinkedAccount::factory()->create(['user_id' => $user->id, 'social_provider_id' => $provider->id]);
        $this->assertTrue($acc->canDelete());
    }

    public function testCanDeleteRequiresOtherAuthProvider()
    {
        $user = User::factory()->create();
        $authProv = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'auth1']);
        $otherAuth = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'auth2']);

        // create two accounts for same user, both auth-enabled -> can delete returns true
        LinkedAccount::factory()->create(['user_id' => $user->id, 'social_provider_id' => $authProv->id]);
        $acc = LinkedAccount::factory()->create(['user_id' => $user->id, 'social_provider_id' => $otherAuth->id]);
        $this->assertTrue($acc->canDelete());
    }
}
