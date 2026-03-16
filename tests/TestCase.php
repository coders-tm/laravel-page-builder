<?php

declare(strict_types=1);

namespace Coderstm\PageBuilder\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        $app->make('config')->set(
            'pagebuilder.pages',
            __DIR__.'/../workbench/resources/views/pages'
        );

        $app->make('config')->set(
            'pagebuilder.templates',
            __DIR__.'/../workbench/resources/views/templates'
        );

        $app->make('config')->set('app.name', 'My App');
    }
}
