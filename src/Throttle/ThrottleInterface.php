<?php

declare(strict_types=1);

namespace Nes\Throttle;

interface ThrottleInterface
{
    public const MAX_FPS = 60.0988139;

    public function throttle(callable $invoke): void;
}
