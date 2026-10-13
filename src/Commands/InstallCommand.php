<?php

declare(strict_types=1);

namespace Filagora\Core\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filagora:install
        {--force : Overwrite the published config and run migrations in production}';

    protected $description = 'Publish the Filagora config, run migrations and check the install';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', ['--tag' => 'filagora-config', '--force' => $force]);

        $this->call('migrate', ['--force' => $force]);

        return $this->call('filagora:doctor');
    }
}
