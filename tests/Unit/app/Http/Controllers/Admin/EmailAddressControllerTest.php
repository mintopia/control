<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EmailAddressController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\EmailAddress;
use App\Http\Requests\Admin\EmailAddressUpdateRequest;
use Illuminate\Support\Facades\Route;

class EmailAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EmailAddressController();
        $this->assertInstanceOf(EmailAddressController::class, $controller);
    }

    public function testStoreCreatesEmailForUser()
    {
        $user = User::factory()->create();
        $request = EmailAddressUpdateRequest::create('/admin/users/' . $user->id . '/emails', 'POST', ['address' => 'test@example.com']);
        $request->setUserResolver(fn() => $user);

        $controller = new EmailAddressController();
        $response = $controller->store($user, $request);

        $this->assertDatabaseHas('email_addresses', ['email' => 'test@example.com']);
    }

    public function testDestroyDeletesEmail()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id]);

        // Ensure route exists for redirects
        Route::get('admin/users/{user?}', fn() => '')->name('admin.users.show');

        // Authenticate as the user to simulate admin action and make deletion deterministic
        $this->actingAs($user);

        // Clear primary email on user and on loaded relation
        $user->primary_email_id = null;
        $user->save();
        $email->load('user');
        $email->user->primary_email_id = null;
        $email->user->save();

        // Ensure no linked accounts block deletion
        if ($email->linkedAccounts()->count() > 0) {
            $email->linkedAccounts()->delete();
            $email->refresh();
        }

        $this->assertTrue($email->canDelete(), 'Email should be deletable in test setup');

        $controller = new EmailAddressController();
        $response = $controller->destroy($user, $email);

        $this->assertDatabaseMissing('email_addresses', ['id' => $email->id]);
    }
}
