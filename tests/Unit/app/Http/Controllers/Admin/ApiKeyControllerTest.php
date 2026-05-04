<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Requests\Admin\ApiKeyStoreRequest;
use App\Http\Requests\Admin\ApiKeyUpdateRequest;
use App\Http\Requests\Admin\DeleteRequest;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\View\View;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_view()
    {
        ApiKey::factory()->count(2)->create();
        $controller = new ApiKeyController;
        $response = $controller->index();
        $this->assertInstanceOf(View::class, $response);
        $this->assertArrayHasKey('apikeys', $response->getData());
    }

    public function test_create_returns_view()
    {
        $controller = new ApiKeyController;
        $response = $controller->create();
        $this->assertInstanceOf(View::class, $response);
        $this->assertArrayHasKey('apikey', $response->getData());
    }

    public function test_store_creates_key_and_flashes_plaintext()
    {
        $req = ApiKeyStoreRequest::create('/', 'POST', ['name' => 'Integration A']);
        $req->setLaravelSession(app('session.store'));

        $controller = new ApiKeyController;
        try {
            $controller->store($req);
        } catch (UrlGenerationException|RouteNotFoundException $ex) {
            // route name not registered in unit test environment; ok
        }

        $this->assertDatabaseHas('api_keys', ['name' => 'Integration A']);
        $plaintext = $req->session()->get('apiKeyPlaintext');
        $this->assertNotNull($plaintext);
        $this->assertStringStartsWith('ctrl_', $plaintext);
    }

    public function test_created_returns_view_with_api_key()
    {
        $key = ApiKey::factory()->create();
        $controller = new ApiKeyController;

        $response = $controller->created($key);

        $this->assertInstanceOf(View::class, $response);
        $this->assertEquals($key->id, $response->getData()['apikey']->id);
    }

    public function test_edit_returns_view()
    {
        $key = ApiKey::factory()->create();
        $controller = new ApiKeyController;
        $response = $controller->edit($key);
        $this->assertInstanceOf(View::class, $response);
        $this->assertEquals($key->id, $response->getData()['apikey']->id);
    }

    public function test_update_changes_name_and_enabled_only()
    {
        $key = ApiKey::factory()->create([
            'name' => 'Old',
            'enabled' => true,
        ]);
        $originalHash = $key->key_hash;

        $req = ApiKeyUpdateRequest::create('/', 'POST', [
            'name' => 'New',
            'enabled' => 0,
            'key_hash' => 'should_not_be_applied',
        ]);

        $controller = new ApiKeyController;
        try {
            $controller->update($req, $key);
        } catch (UrlGenerationException|RouteNotFoundException $ex) {
            // ok
        }

        $fresh = $key->fresh();
        $this->assertEquals('New', $fresh->name);
        $this->assertFalse($fresh->enabled);
        $this->assertEquals($originalHash, $fresh->key_hash);
    }

    public function test_delete_returns_view()
    {
        $key = ApiKey::factory()->create();
        $controller = new ApiKeyController;
        $response = $controller->delete($key);
        $this->assertInstanceOf(View::class, $response);
    }

    public function test_destroy_removes_row()
    {
        $key = ApiKey::factory()->create();
        $req = DeleteRequest::create('/', 'DELETE', ['confirm' => 'delete']);

        $controller = new ApiKeyController;
        try {
            $controller->destroy($req, $key);
        } catch (UrlGenerationException|RouteNotFoundException $ex) {
            // ok
        }

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
    }
}
