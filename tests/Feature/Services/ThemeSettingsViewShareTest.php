<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Feature\Services;

use PageBuilder\Services\ThemeSettings;
use PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ThemeSettingsViewShareTest extends TestCase
{
    private string $valuesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valuesPath = sys_get_temp_dir().'/pb-theme-view-test.json';
        $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->valuesPath)) {
            File::delete($this->valuesPath);
        }

        parent::tearDown();
    }

    public function test_theme_is_shared_with_all_views(): void
    {
        $shared = View::shared('theme');

        $this->assertNotNull($shared);
        $this->assertInstanceOf(ThemeSettings::class, $shared);
    }

    public function test_theme_shared_instance_is_same_singleton(): void
    {
        $shared = View::shared('theme');
        $singleton = $this->app->make(ThemeSettings::class);

        $this->assertSame($singleton, $shared);
    }

    public function test_theme_property_access_in_blade_view(): void
    {
        // Write values that Blade will read via $theme->primary_color
        $themeSettings = $this->app->make(ThemeSettings::class);
        $themeSettings->save(['primary_color' => '#FACADE']);

        $html = $this->renderInlineBladeView('{{ $theme->primary_color }}');

        $this->assertSame('#FACADE', trim($html));
    }

    public function test_theme_get_with_default_in_blade_view(): void
    {
        $html = $this->renderInlineBladeView(
            '{{ $theme->get(\'missing_key\', \'default-value\') }}'
        );

        $this->assertSame('default-value', trim($html));
    }

    public function test_theme_null_coalescing_in_blade_view(): void
    {
        $html = $this->renderInlineBladeView(
            '{{ $theme->undefined_key ?? \'fallback\' }}'
        );

        $this->assertSame('fallback', trim($html));
    }

    public function test_theme_reflects_updated_values_after_save(): void
    {
        $themeSettings = $this->app->make(ThemeSettings::class);
        $themeSettings->save(['primary_color' => '#BEFORE']);

        $themeSettings->save(['primary_color' => '#AFTER']);

        $html = $this->renderInlineBladeView('{{ $theme->primary_color }}');

        $this->assertSame('#AFTER', trim($html));
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function renderInlineBladeView(string $bladeString): string
    {
        $tmpFile = sys_get_temp_dir().'/pb-blade-test-'.uniqid().'.blade.php';
        File::put($tmpFile, $bladeString);

        // Register a one-off view path so Blade can find the temp file
        $viewName = 'pb-test-'.md5($tmpFile);
        $this->app['view']->addLocation(sys_get_temp_dir());

        ob_start();
        echo view()->file($tmpFile)->render();

        return ob_get_clean();
    }
}
