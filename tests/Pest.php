<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use PageBuilder\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');
uses(RefreshDatabase::class)->in('Feature');
