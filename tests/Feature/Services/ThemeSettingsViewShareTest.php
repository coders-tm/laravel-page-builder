<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use PageBuilder\Services\ThemeSettings;

beforeEach(function () {
    $this->valuesPath = sys_get_temp_dir().'/pb-theme-view-test.json';
    $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);
});
afterEach(function () {
    if (File::exists($this->valuesPath)) {
        File::delete($this->valuesPath);
    }

});
test('theme is shared with all views', function () {
    $shared = View::shared('theme');

    expect($shared)->not->toBeNull();
    expect($shared)->toBeInstanceOf(ThemeSettings::class);
});
test('theme shared instance is same singleton', function () {
    $shared = View::shared('theme');
    $singleton = $this->app->make(ThemeSettings::class);

    expect($shared)->toBe($singleton);
});
test('theme property access in blade view', function () {
    // Write values that Blade will read via $theme->primary_color
    $themeSettings = $this->app->make(ThemeSettings::class);
    $themeSettings->save(['primary_color' => '#FACADE']);

    $html = renderInlineBladeView('{{ $theme->primary_color }}');

    expect(trim($html))->toBe('#FACADE');
});
test('theme get with default in blade view', function () {
    $html = renderInlineBladeView('{{ $theme->get(\'missing_key\', \'default-value\') }}');

    expect(trim($html))->toBe('default-value');
});
test('theme null coalescing in blade view', function () {
    $html = renderInlineBladeView('{{ $theme->undefined_key ?? \'fallback\' }}');

    expect(trim($html))->toBe('fallback');
});
test('theme reflects updated values after save', function () {
    $themeSettings = $this->app->make(ThemeSettings::class);
    $themeSettings->save(['primary_color' => '#BEFORE']);

    $themeSettings->save(['primary_color' => '#AFTER']);

    $html = renderInlineBladeView('{{ $theme->primary_color }}');

    expect(trim($html))->toBe('#AFTER');
});
// ─── Helper ──────────────────────────────────────────────────────────────
function renderInlineBladeView(string $bladeString): string
{
    $tmpFile = sys_get_temp_dir().'/pb-blade-test-'.uniqid().'.blade.php';
    File::put($tmpFile, $bladeString);

    app('view')->addLocation(sys_get_temp_dir());

    ob_start();
    echo view()->file($tmpFile)->render();

    return ob_get_clean();
}
