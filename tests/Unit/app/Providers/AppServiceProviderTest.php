<?php

namespace Tests\Unit\App\Providers;

use Tests\TestCase;

use App\Models\Theme;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Mockery;


class AppServiceProviderTest extends TestCase
{
    public function testBladeDirectiveIsRegistered()
    {
        Blade::shouldReceive('directive')->once();
        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();
    }

    public function testViewComposerSetsThemeVariables()
    {
        // This currently fails due to
        // Illuminate\Database\QueryException: could not find driver (Connection: mysql, SQL: select * from `themes` where `active` = 1 limit 1)

        $theme = new Theme();
        $theme->dark_mode = true;

        // Use Eloquent's partial mock for Theme
        $themeQuery = $this->partialMock(Theme::class, function ($mock) use ($theme) {
            $mock->shouldReceive('whereActive')->with(true)->andReturnSelf();
            $mock->shouldReceive('first')->andReturn($theme);
        });
        $themeQuery->shouldReceive('whereActive')->with(true)->andReturnSelf();
        $themeQuery->shouldReceive('first')->andReturn($theme);

        $view = $this->mock(\Illuminate\View\View::class);
        $view->shouldReceive('with')->with('currentTheme', $theme);
        $view->shouldReceive('with')->with('darkMode', true);
        View::shouldReceive('composer')->andReturnUsing(function ($views, $callback) use ($view) {
            $callback($view);
        });
        $provider = new \App\Providers\AppServiceProvider(app());
        $provider->boot();
    }
}
