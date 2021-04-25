<?php

declare(strict_types=1);

namespace Nes\Bus;

class Chr
{
    use RomTrait;

    public function write(int $addr, int $val): void
    {
        $this->rom[$addr] = $val;
    }
}
