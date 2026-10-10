<?php

declare(strict_types=1);

namespace ForumKit\Core\Commands;

use ForumKit\Core\Doctor\CheckStatus;
use ForumKit\Core\Doctor\Doctor;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    protected $signature = 'forumkit:doctor';

    protected $description = 'Check that ForumKit is installed and configured correctly';

    public function handle(Doctor $doctor): int
    {
        $results = $doctor->run();

        $this->table(
            ['', 'Check', 'Result'],
            array_map(fn ($result): array => [
                match ($result->status) {
                    CheckStatus::Pass => '<info>PASS</info>',
                    CheckStatus::Warn => '<comment>WARN</comment>',
                    CheckStatus::Fail => '<error>FAIL</error>',
                },
                $result->name,
                $result->message,
            ], $results),
        );

        if (! Doctor::passed($results)) {
            $this->error('One or more checks failed.');

            return self::FAILURE;
        }

        $this->info('All checks passed.');

        return self::SUCCESS;
    }
}
