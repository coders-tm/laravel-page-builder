<?php declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PageBuilder\Registry\RegistryEntry;
use PageBuilder\Registry\SectionRegistry;
use PageBuilder\Schema\SectionSchema;

beforeEach(function () {
    $this->registry = $this->app->make(SectionRegistry::class);
});
test('auto discovers sections from path', function () {
    // The sections path is already added via config in TestCase::defineEnvironment
    expect($this->registry->has('hero'))->toBeTrue();

    $entry = $this->registry->get('hero');
    expect($entry)->toBeInstanceOf(RegistryEntry::class);
    expect($entry->schema)->toBeInstanceOf(SectionSchema::class);
    expect($entry->schema->name)->toBe('Hero');
});
test('types returns all registered types', function () {
    $types = $this->registry->types();
    expect($types)->toContain('hero');
    expect($types)->toContain('footer');
});
test('has returns false for unregistered', function () {
    expect($this->registry->has('nonexistent'))->toBeFalse();
});
test('get returns null for unregistered', function () {
    expect($this->registry->get('nonexistent'))->toBeNull();
});
test('register manual schema', function () {
    $schema = new SectionSchema([
        'name' => 'Custom Section',
        'settings' => [],
    ]);

    $this->registry->register('custom', $schema);

    expect($this->registry->has('custom'))->toBeTrue();
    $entry = $this->registry->get('custom');
    expect($entry->schema->name)->toBe('Custom Section');
    expect($entry->view)->toBe('sections.custom');
});
test('get all returns all entries', function () {
    $all = $this->registry->get();
    expect($all)->toBeArray();
    expect($all)->toHaveKey('hero');
    expect($all['hero']->schema)->toBeInstanceOf(SectionSchema::class);
});
test('has returns false for invalid schema', function () {
    expect($this->registry->has('non-schema-file-name'))->toBeFalse();
});
test('section with blocks and presets', function () {
    $entry = $this->registry->get('content');
    $schema = $entry->schema;

    expect($schema->name)->toBe('Content');
    expect($schema->settings)->toHaveCount(0);

    // Only entries with both 'type' and 'name' become BlockSchema objects in $blocks.
    // The bare ['type' => '@theme'] entry goes into $allowedBlockTypes, not $blocks.
    expect($schema->blocks)->toHaveCount(1);
    expect($schema->allowedBlockTypes)->toHaveCount(1);
    expect($schema->presets)->toHaveCount(1);
    expect($schema->acceptsThemeBlocks())->toBeFalse();
});
