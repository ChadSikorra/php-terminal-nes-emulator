<?php

declare(strict_types=1);

namespace Nes\Ppu;

interface PpuInterface
{
    public function run(int $cycle): ?RenderingData;

    public function read(int $addr): int;

    public function write(int $addr, int $data): void;

    public function transferSprite(int $index, int $data): void;
}
