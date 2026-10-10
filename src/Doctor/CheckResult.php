<?php

declare(strict_types=1);

namespace ForumKit\Core\Doctor;

final readonly class CheckResult
{
    public function __construct(
        public CheckStatus $status,
        public string $name,
        public string $message,
    ) {}

    public static function pass(string $name, string $message): self
    {
        return new self(CheckStatus::Pass, $name, $message);
    }

    public static function warn(string $name, string $message): self
    {
        return new self(CheckStatus::Warn, $name, $message);
    }

    public static function fail(string $name, string $message): self
    {
        return new self(CheckStatus::Fail, $name, $message);
    }
}
