<?php

declare(strict_types=1);

namespace ForumKit\Core\Doctor;

enum CheckStatus: string
{
    case Pass = 'pass';
    case Warn = 'warn';
    case Fail = 'fail';
}
