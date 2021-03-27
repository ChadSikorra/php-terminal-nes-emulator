<?php

declare(strict_types=1);

namespace Nes\Throttle;

class PhpThrottle implements ThrottleInterface
{
    private float $maxFps;

    private float $timePerFrame;

    private ?float $startedAt;

    public function __construct(float $maxFps = self::MAX_FPS)
    {
        $this->maxFps = $maxFps;
        $this->timePerFrame = 1000.0 / ($maxFps * 1.0);
    }

    public function throttle(callable $invoke): void
    {
        $this->startedAt = $this->startedAt ?? microtime(true);
        $invoke();

        // @TODO Still something wrong below. 300 should not be correct. Logic seems wrong...
        $sleepTime = $this->timePerFrame - (microtime(true) - $this->startedAt);
        if($sleepTime > 0) {
            usleep((int)ceil($sleepTime * 300));
            $this->startedAt = microtime(true);
        }
    }
}
