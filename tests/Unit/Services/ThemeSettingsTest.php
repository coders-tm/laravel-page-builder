<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests\Unit\Services;

use Coderstm\PageBuilder\Services\PageCache;
use Coderstm\PageBuilder\Services\ThemeSettings;
use Coderstm\PageBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ThemeSettingsTest extends TestCase
{
    private string $valuesPath;

    private ThemeSettings $themeSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valuesPath = sys_get_temp_dir().'/pb-theme-settings-test.json';

        $this->app['config']->set('pagebuilder.theme_settings_path', $this->valuesPath);
        $this->app['config']->set('pagebuilder.theme_settings_schema', [
            [
                'name' => 'Colors',
                'settings' => [
                    ['id' => 'primary_color', 'type' => 'color', 'label' => 'Primary', 'default' => '#3B82F6'],
                    ['id' => 'font_family',   'type' => 'select', 'label' => 'Font', 'default' => 'sans'],
                ],
            ],
        ]);

        // Fresh instance so it picks up the config set above
        $this->themeSettings = new ThemeSettings($this->app->make(PageCache::class));
    }

    protected function tearDown(): void
    {
        if (File::exists($this->valuesPath)) {
            File::delete($this->valuesPath);
        }

        parent::tearDown();
    }

    // ─── get() ───────────────────────────────────────────────────────────────

    public function test_get_returns_null_when_no_values_file_exists(): void
    {
        $this->assertNull($this->themeSettings->get('primary_color'));
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('#3B82F6', $this->themeSettings->get('primary_color', '#3B82F6'));
    }

    public function test_get_returns_saved_value(): void
    {
        $this->themeSettings->save(['primary_color' => '#FF0000']);

        $this->assertSame('#FF0000', $this->themeSettings->get('primary_color'));
    }

    public function test_get_returns_default_when_key_not_in_saved_values(): void
    {
        $this->themeSettings->save(['primary_color' => '#FF0000']);

        $this->assertSame('fallback', $this->themeSettings->get('missing_key', 'fallback'));
    }

    // ─── __get() magic property access ───────────────────────────────────────

    public function test_magic_get_returns_null_for_missing_key(): void
    {
        $this->assertNull($this->themeSettings->primary_color);
    }

    public function test_magic_get_returns_saved_value(): void
    {
        $this->themeSettings->save(['primary_color' => '#00FF00', 'font_family' => 'serif']);

        $this->assertSame('#00FF00', $this->themeSettings->primary_color);
        $this->assertSame('serif', $this->themeSettings->font_family);
    }

    public function test_magic_get_returns_null_for_unknown_key(): void
    {
        $this->themeSettings->save(['primary_color' => '#000']);

        $this->assertNull($this->themeSettings->unknown_key);
    }

    // ─── save() / values() cache ─────────────────────────────────────────────

    public function test_save_persists_values_to_disk(): void
    {
        $values = ['primary_color' => '#123456', 'font_family' => 'mono'];

        $this->assertTrue($this->themeSettings->save($values));
        $this->assertFileExists($this->valuesPath);

        $raw = json_decode(File::get($this->valuesPath), true);
        $this->assertSame($values, $raw);
    }

    public function test_values_are_loaded_from_disk(): void
    {
        File::put($this->valuesPath, json_encode(['primary_color' => '#ABCDEF']));

        $fresh = new ThemeSettings($this->app->make(PageCache::class));
        $this->assertSame(['primary_color' => '#ABCDEF'], $fresh->values());
    }

    public function test_values_cache_is_refreshed_after_save(): void
    {
        $this->themeSettings->save(['primary_color' => '#111111']);
        $this->themeSettings->save(['primary_color' => '#222222']);

        $this->assertSame('#222222', $this->themeSettings->get('primary_color'));
    }

    public function test_values_returns_empty_array_when_no_file(): void
    {
        $this->assertSame([], $this->themeSettings->values());
    }

    // ─── schema() ────────────────────────────────────────────────────────────

    public function test_schema_returns_config_schema(): void
    {
        $schema = $this->themeSettings->schema();

        $this->assertCount(1, $schema);
        $this->assertSame('Colors', $schema[0]['name']);
        $this->assertCount(2, $schema[0]['settings']);
    }

    // ─── toArray() ───────────────────────────────────────────────────────────

    public function test_to_array_contains_schema_and_values_keys(): void
    {
        $this->themeSettings->save(['primary_color' => '#FF00FF']);

        $result = $this->themeSettings->toArray();

        $this->assertArrayHasKey('schema', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertSame(['primary_color' => '#FF00FF'], $result['values']);
    }
}
