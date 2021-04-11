<?php

declare(strict_types=1);

namespace Nes\Apu;

interface ChannelInterface
{
    public function output(): int;
}
