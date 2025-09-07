<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LinkedAccountController;
use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Tests\TestCase;

class LinkedAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new LinkedAccountController();
        $this->assertInstanceOf(LinkedAccountController::class, $controller);
    }

    public function testDeleteShowsViewWhenAccountCanBeDeleted()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => true]);

        // Create two accounts for the user so canDelete() will allow deletion
        $account1 = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();
        $account2 = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();

        $controller = new LinkedAccountController();
        $response = $controller->delete($user, $account1);

        $this->assertInstanceOf(View::class, $response);
        // View data should include the account we passed
        $this->assertArrayHasKey('account', $response->getData());
        $this->assertEquals($account1->id, $response->getData()['account']->id);
    }

    public function testDeleteRedirectsWhenAccountCannotBeDeleted()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => true]);

        // Single account -> cannot delete
        $account = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();

        $controller = new LinkedAccountController();
        $response = $controller->delete($user, $account);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        // Account should still exist in DB
        $this->assertDatabaseHas('linked_accounts', ['id' => $account->id]);
    }

    public function testDestroyDeletesAccountWhenAllowed()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => true]);

        $account1 = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();
        $account2 = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();

        $controller = new LinkedAccountController();
        $response = $controller->destroy($user, $account1);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseMissing('linked_accounts', ['id' => $account1->id]);
    }

    public function testDestroyDoesNotDeleteWhenOnlyOneAccount()
    {
        $user = User::factory()->create();
        $provider = SocialProvider::factory()->create(['auth_enabled' => true]);

        $account = LinkedAccount::factory()->for($user)->for($provider, 'provider')->create();

        $controller = new LinkedAccountController();
        $response = $controller->destroy($user, $account);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('linked_accounts', ['id' => $account->id]);
    }
}
