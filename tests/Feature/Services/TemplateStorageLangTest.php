<?php

declare(strict_types=1);

/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\File;
use PageBuilder\PageBuilder;
use PageBuilder\Services\TemplateStorage;

afterEach(function () {
    PageBuilder::setLang(null);
});

test('load falls back to default when lang is set but locale template missing', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/page.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content']],
            'order' => ['main'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');

        $storage = new TemplateStorage;
        $data = $storage->load('page');

        expect($data)->toBeArray();
        expect($data['order'])->toBe(['main']);
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('load reads from locale-specific template when it exists', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        // Default template
        file_put_contents($customPath.'/page.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content']],
            'order' => ['main'],
        ]));

        // French template with different sections
        file_put_contents($customPath.'/page.fr.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content'], 'hero' => ['type' => 'hero']],
            'order' => ['hero', 'main'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');

        $storage = new TemplateStorage;
        $data = $storage->load('page');

        expect($data)->toBeArray();
        expect($data['order'])->toBe(['hero', 'main']);
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('load uses default template when lang is null', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/page.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content']],
            'order' => ['main'],
        ]));

        file_put_contents($customPath.'/page.fr.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content'], 'hero' => ['type' => 'hero']],
            'order' => ['hero', 'main'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        // No lang set
        $storage = new TemplateStorage;
        $data = $storage->load('page');

        expect($data['order'])->toBe(['main']);
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('load returns null when both locale and default templates missing', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');

        $storage = new TemplateStorage;
        expect($storage->load('nonexistent'))->toBeNull();
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('locale template takes precedence over default template', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/page.json', json_encode([
            'sections' => ['s1' => ['type' => 'hero']],
            'order' => ['s1'],
        ]));

        file_put_contents($customPath.'/page.fr.json', json_encode([
            'sections' => ['s2' => ['type' => 'banner']],
            'order' => ['s2'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');

        $storage = new TemplateStorage;
        $data = $storage->load('page');

        // Should use French template
        expect($data['sections'])->toHaveKey('s2');
        expect($data['sections'])->not->toHaveKey('s1');
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('different langs load different templates', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/page.fr.json', json_encode([
            'sections' => ['fr_section' => ['type' => 'hero']],
            'order' => ['fr_section'],
        ]));

        file_put_contents($customPath.'/page.de.json', json_encode([
            'sections' => ['de_section' => ['type' => 'banner']],
            'order' => ['de_section'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');
        $fr = (new TemplateStorage)->load('page');
        expect($fr['order'])->toBe(['fr_section']);

        PageBuilder::setLang('de');
        $de = (new TemplateStorage)->load('page');
        expect($de['order'])->toBe(['de_section']);
    } finally {
        File::deleteDirectory($customPath);
    }
});

test('non-page templates also support lang', function () {
    $customPath = sys_get_temp_dir().'/pb_test_tpl_lang_'.uniqid();
    mkdir($customPath);

    try {
        file_put_contents($customPath.'/blog.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content']],
            'order' => ['main'],
        ]));

        file_put_contents($customPath.'/blog.fr.json', json_encode([
            'sections' => ['main' => ['type' => 'page-content'], 'sidebar' => ['type' => 'sidebar']],
            'order' => ['main', 'sidebar'],
        ]));

        config(['pagebuilder.templates' => $customPath]);

        PageBuilder::setLang('fr');

        $storage = new TemplateStorage;
        $data = $storage->load('blog');

        expect($data['order'])->toBe(['main', 'sidebar']);
    } finally {
        File::deleteDirectory($customPath);
    }
});
