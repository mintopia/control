<?php

namespace Tests\Unit\app\Providers;

use App\Models\Theme;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Factory;
use Mockery\MockInterface;
use ReflectionClass;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function clearBladeDirectives()
    {
        $compiler = Blade::getFacadeRoot();
        $ref = new ReflectionClass($compiler);
        if ($ref->hasProperty('customDirectives')) {
            $prop = $ref->getProperty('customDirectives');
            $prop->setAccessible(true);
            $prop->setValue($compiler, []);
        }
    }

    public function testBladeSettingDirectiveIsRegistered()
    {
        // Ensure no existing directive conflicts
        $this->clearBladeDirectives();

        $provider = new AppServiceProvider(app());
        $provider->boot();

        $directives = Blade::getCustomDirectives();
        $this->assertArrayHasKey('setting', $directives);

        $closure = $directives['setting'];
        $result = $closure("'site_name'", 'Default');
        $this->assertIsString($result);
        $this->assertStringContainsString('App\\Models\\Setting::fetch', $result);
    }

    public function testBladeSettingDirectiveReturnsDefaultWhenSettingNotFound()
    {
        // Ensure no existing directive conflicts
        $this->clearBladeDirectives();

        $provider = new AppServiceProvider(app());
        $provider->boot();

        $directives = Blade::getCustomDirectives();
        $this->assertArrayHasKey('setting', $directives);

        $closure = $directives['setting'];
        $result = $closure("'non_existent_setting'", 'Default Value');
        // Blade directives return PHP code to be rendered later; ensure the generated code contains the default value
        $this->assertIsString($result);
        $this->assertStringContainsString('Default Value', $result);
    }

    public function testViewComposerSetsThemeAndDarkMode()
    {
        // Create a real theme in the database so Theme::whereActive(true)->first() returns it
        Theme::factory()->create([
            'dark_mode' => 1,
            'active' => 1,
        ]);

        // Fake view factory that captures composer closures
        $viewClosure = null;
        $mockView = $this->partialMock(Factory::class, function (MockInterface $mock) use (&$viewClosure) {
            $mock->shouldReceive('composer')->andReturnUsing(
                function ($views, $closure) use (&$viewClosure) {
                    $viewClosure = $closure;
                }
            );
        });

        // Bind our fake view factory into the container so the provider will call ->composer()
        app()->instance('view', $mockView);

        $provider = new AppServiceProvider(app());
        $provider->boot();

        // Ensure a closure was registered
        $this->assertIsCallable($viewClosure);

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
        ($viewClosure)($view);

        $this->assertArrayHasKey('currentTheme', $view->data);
        $this->assertArrayHasKey('darkMode', $view->data);
    }
}
