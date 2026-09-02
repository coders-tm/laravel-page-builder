<?php

declare(strict_types=1);

namespace PageBuilder\Tests\Unit\Components;

use PageBuilder\Components\Settings;
use PageBuilder\Schema\SettingSchema;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function test_get_returns_value(): void
    {
        $settings = new Settings(
            ['title' => 'Hello', 'color' => '#fff'],
            []
        );

        $this->assertSame('Hello', $settings->get('title'));
        $this->assertSame('#fff', $settings->get('color'));
    }

    public function test_get_falls_back_to_default(): void
    {
        $settings = new Settings(
            [],
            ['title' => 'Default Title']
        );

        $this->assertSame('Default Title', $settings->get('title'));
    }

    public function test_get_returns_explicit_fallback(): void
    {
        $settings = new Settings([], []);

        $this->assertSame('fallback', $settings->get('missing', 'fallback'));
    }

    public function test_has_checks_values_and_defaults(): void
    {
        $settings = new Settings(
            ['title' => 'Hello'],
            ['subtitle' => 'Default']
        );

        $this->assertTrue($settings->has('title'));
        $this->assertTrue($settings->has('subtitle'));
        $this->assertFalse($settings->has('nonexistent'));
    }

    public function test_all_merges_defaults_with_values(): void
    {
        $settings = new Settings(
            ['title' => 'Custom'],
            ['title' => 'Default', 'subtitle' => 'Default Sub']
        );

        $all = $settings->all();
        $this->assertSame('Custom', $all['title']);
        $this->assertSame('Default Sub', $all['subtitle']);
    }

    public function test_raw_returns_only_explicit_values(): void
    {
        $settings = new Settings(
            ['title' => 'Hello'],
            ['title' => 'Default', 'subtitle' => 'Sub']
        );

        $raw = $settings->raw();
        $this->assertSame(['title' => 'Hello'], $raw);
    }

    public function test_defaults_returns_only_defaults(): void
    {
        $settings = new Settings(
            ['title' => 'Hello'],
            ['title' => 'Default', 'subtitle' => 'Sub']
        );

        $defaults = $settings->defaults();
        $this->assertSame(['title' => 'Default', 'subtitle' => 'Sub'], $defaults);
    }

    public function test_magic_get(): void
    {
        $settings = new Settings(['title' => 'Hello'], []);

        $this->assertSame('Hello', $settings->title);
    }

    public function test_magic_isset(): void
    {
        $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

        $this->assertTrue(isset($settings->title));
        $this->assertTrue(isset($settings->color));
        $this->assertFalse(isset($settings->missing));
    }

    public function test_invokable(): void
    {
        $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

        $this->assertSame('Hello', $settings('title'));
        $this->assertSame('#000', $settings('color'));
        $this->assertSame('fallback', $settings('missing', 'fallback'));
    }

    public function test_to_string(): void
    {
        $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

        // Settings __toString returns empty string (not JSON — use toArray/jsonSerialize for that)
        $this->assertSame('', (string) $settings);
    }

    public function test_array_access(): void
    {
        $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);

        $this->assertTrue(isset($settings['title']));
        $this->assertSame('Hello', $settings['title']);
        $this->assertSame('#000', $settings['color']);
    }

    public function test_array_access_set_mutates(): void
    {
        $settings = new Settings([], []);

        // Settings allows writes via ArrayAccess
        $settings['title'] = 'value';
        $this->assertSame('value', $settings['title']);
    }

    public function test_array_access_unset_removes_value(): void
    {
        $settings = new Settings(['title' => 'Hello'], []);

        // Settings allows unset via ArrayAccess
        unset($settings['title']);
        $this->assertNull($settings->get('title'));
    }

    public function test_to_array(): void
    {
        $settings = new Settings(
            ['title' => 'Custom'],
            ['title' => 'Default', 'subtitle' => 'Sub']
        );

        $array = $settings->toArray();
        $this->assertSame('Custom', $array['title']);
        $this->assertSame('Sub', $array['subtitle']);
    }

    public function test_json_serializable(): void
    {
        $settings = new Settings(['title' => 'Hello'], ['color' => '#000']);
        $json = json_encode($settings);
        $decoded = json_decode($json, true);

        $this->assertSame('Hello', $decoded['title']);
        $this->assertSame('#000', $decoded['color']);
    }

    public function test_from_schema(): void
    {
        $schemas = [
            new SettingSchema(['id' => 'title', 'type' => 'text', 'default' => 'Hello']),
            new SettingSchema(['id' => 'color', 'type' => 'color', 'default' => '#000']),
        ];

        $settings = Settings::fromSchema(['title' => 'Custom'], $schemas);

        $this->assertSame('Custom', $settings->get('title'));
        $this->assertSame('#000', $settings->get('color'));
        $this->assertSame(['title' => 'Hello', 'color' => '#000'], $settings->defaults());
    }
}
