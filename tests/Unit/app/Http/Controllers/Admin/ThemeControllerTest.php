<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\ThemeController;
use App\Http\Requests\Admin\ThemeUpdateRequest;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateEditDelete()
    {
        $theme = Theme::factory()->create(['readonly' => false]);
        $c = new ThemeController();
        $this->assertTrue(is_object($c->create()));
        $this->assertTrue(is_object($c->edit($theme)));
        $this->assertTrue(is_object($c->delete($theme)));
    }

    public function testStoreAndUpdateObjectHandlesReadonly()
    {
        $controller = new ThemeController();
        $themePayload = [
            'name' => 'T1',
            'css' => '.a{}',
            'active' => 1,
            'dark_mode' => 1,
            'primary' => '#fff',
            'nav_background' => '#000',
            'seat_available' => '#0f0',
            'seat_disabled' => '#ccc',
            'seat_taken' => '#f00',
            'seat_clan' => '#00f',
            'seat_selected' => '#ff0'
        ];
        $req = ThemeUpdateRequest::create('/', 'POST', $themePayload);

        try {
            $controller->store($req);
        } catch (UrlGenerationException $ex) {
            // ok if redirect route not registered
        }

        $theme = Theme::whereName('T1')->first();
        $this->assertNotNull($theme);
        // test updateObject on readonly true does not change name/css
        $theme->readonly = true;
        $theme->save();

        // ensure overrides (active/dark_mode) replace the base payload values
        $req2 = ThemeUpdateRequest::create('/', 'POST', array_merge($themePayload, ['name' => 'Changed', 'css' => '.b{}', 'active' => 0, 'dark_mode' => 0]));
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $theme, $req2);

        $this->assertEquals(0, $theme->fresh()->active);
        $this->assertEquals(0, $theme->fresh()->dark_mode);
        $this->assertEquals('T1', $theme->fresh()->name, 'readonly prevents changing name');
    }

    public function testUpdateAndDestroyBehaviours()
    {
        $theme = Theme::factory()->create(['readonly' => false, 'name' => 'Old']);
        $controller = new ThemeController();

        $themePayload = [
            'primary' => '#fff',
            'nav_background' => '#000',
            'seat_available' => '#0f0',
            'seat_disabled' => '#ccc',
            'seat_taken' => '#f00',
            'seat_clan' => '#00f',
            'seat_selected' => '#ff0'
        ];

        $req = ThemeUpdateRequest::create('/', 'POST', array_merge(['name' => 'New', 'css' => '.x{}', 'active' => 1, 'dark_mode' => 0], $themePayload));
        try {
            $controller->update($req, $theme);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertDatabaseHas('themes', ['id' => $theme->id, 'name' => 'New']);

        // destroy
        $resp = $controller->destroy($theme);
        $this->assertTrue(method_exists($resp, 'getTargetUrl'));
    }

    public function testDeleteAndDestroyAbortWhenReadonly()
    {
        $theme = Theme::factory()->create(['readonly' => true]);
        $controller = new ThemeController();
        // delete() should abort for readonly
        $this->expectException(HttpException::class);
        $controller->delete($theme);
    }

    public function testCreateElseUsesNewThemeWhenNoDefault()
    {
        // ensure no default theme exists
        Theme::whereCode('default')->delete();
        $controller = new ThemeController();
        $resp = $controller->create();
        $this->assertTrue(is_object($resp));
        $this->assertArrayHasKey('theme', $resp->getData());
        $this->assertNull($resp->getData()['theme']->name);
    }

    public function testDestroyAbortsWhenReadonly()
    {
        $theme = Theme::factory()->create(['readonly' => true]);
        $controller = new ThemeController();
        $this->expectException(HttpException::class);
        $controller->destroy($theme);
    }
}
