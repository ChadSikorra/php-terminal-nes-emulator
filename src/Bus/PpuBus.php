<?php

namespace Nes\Bus;

class PpuBus
{
    public Ram $characterRam;

    public function __construct(Ram $characterRam)
    {
        $this->characterRam = $characterRam;
    }

    public function readByPpu(int $addr): int
    {
        return $this->characterRam->read($addr);
    }

    public function writeByPpu(int $addr, int $data): void
    {
        $this->characterRam->write($addr, $data);
    }
}
