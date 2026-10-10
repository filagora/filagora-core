<?php

declare(strict_types=1);

namespace ForumKit\Example\Tests;

use ForumKit\Example\PackageServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PackageServiceProvider::class,
        ];
    }
}
