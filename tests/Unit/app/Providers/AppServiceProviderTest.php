<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;

use App\Models\Theme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Mockery;


class AppServiceProviderTest extends TestCase
{
    //FIXME Test not working - "Blade setting directive is registered"
    public function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    public function test_blade_setting_directive_is_registered()
    {
        $blade = \Mockery::mock(['alias' => 'Illuminate\\Support\\Facades\\Blade']);
        $blade->shouldReceive('directive')
            ->once()
            ->withArgs(function ($name, $closure) {
                if ($name !== 'setting' || !is_callable($closure)) {
                    return false;
                }
                // Simulate closure output
                $result = $closure("'site_name'", 'Default');
                return is_string($result) && str_contains($result, 'App\\Models\\Setting::fetch');
            })
            ->andReturnTrue();

        // Swap the Blade facade in the container
        app()->instance('blade.compiler', $blade);

        $provider = new \App\Providers\AppServiceProvider(app());
    }
    public function test_view_composer_sets_theme_and_dark_mode()
    {
        $theme = new \stdClass();
        $theme->dark_mode = true;

        $themeMock = \Mockery::mock(['alias' => 'App\\Models\\Theme']);
        $themeMock->shouldReceive('whereActive')->with(true)->once()->andReturnSelf();
        $themeMock->shouldReceive('first')->once()->andReturn($theme);

        $viewMock = \Mockery::mock();
        $viewMock->shouldReceive('with')->with('currentTheme', $theme)->once()->andReturnSelf();
        $viewMock->shouldReceive('with')->with('darkMode', true)->once()->andReturnSelf();

        $composer = null;
        $viewFacade = \Mockery::mock(['alias' => 'Illuminate\\Support\\Facades\\View']);
        $viewFacade->shouldReceive('composer')
            ->with(['layouts.app', 'layouts.login'], \Mockery::on(function ($closure) use (&$composer, $viewMock) {
                $composer = $closure;
                return true;
            }))
            ->once();

        // Swap the View facade in the container
        app()->instance('view', $viewFacade);

        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();

        // Now test the composer closure
        if (is_callable($composer)) {
            $composer($viewMock);
        }
    }
}
