<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;

use App\Models\Theme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;


class AppServiceProviderTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_blade_setting_directive_is_registered()
    {
        // Ensure no existing directive conflicts
        Blade::flushDirectives();

        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();

        $directives = Blade::getCustomDirectives();
        $this->assertArrayHasKey('setting', $directives);

        $closure = $directives['setting'];
        $result = $closure("'site_name'", 'Default');
        $this->assertIsString($result);
        $this->assertStringContainsString('App\\Models\\Setting::fetch', $result);
    }
    
    public function test_blade_setting_directive_returns_default_when_setting_not_found()
    {
        // Ensure no existing directive conflicts
        Blade::flushDirectives();

        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();

        $directives = Blade::getCustomDirectives();
        $this->assertArrayHasKey('setting', $directives);

        $closure = $directives['setting'];
        $result = $closure("'non_existent_setting'", 'Default Value');
        $this->assertEquals('Default Value', $result);
    }

    public function test_view_composer_sets_theme_and_dark_mode()
    {
        // Create a real theme in the database so Theme::whereActive(true)->first() returns it
        Theme::factory()->create([
            'dark_mode' => 1,
            'active' => 1,
        ]);

        // Fake view factory that captures composer closures
        $fakeView = new class {
            public $registered = null;
            public function composer($views, $closure)
            {
                // store the closure for later invocation
                $this->registered = $closure;
            }
        };

        // Bind our fake view factory into the container so the provider will call ->composer()
        app()->instance('view', $fakeView);

        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();

        // Ensure a closure was registered
        $this->assertIsCallable($fakeView->registered);

        // Simulate a view instance that has ->with()
        $view = new class {
            public $data = [];
            public function with($key, $value)
            {
                $this->data[$key] = $value;
                return $this;
            }
        };

        // Invoke captured composer closure
        ($fakeView->registered)($view);

        $this->assertArrayHasKey('currentTheme', $view->data);
        $this->assertArrayHasKey('darkMode', $view->data);
    }
}
