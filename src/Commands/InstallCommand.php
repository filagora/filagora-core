<?php

declare(strict_types=1);

namespace ForumKit\Core\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'forumkit:install
        {--force : Overwrite the published config and run migrations in production}';

    protected $description = 'Publish the ForumKit config, run migrations and check the install';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', ['--tag' => 'forumkit-config', '--force' => $force]);

        $this->call('migrate', ['--force' => $force]);

        return $this->call('forumkit:doctor');
    }
}
