<?php

declare(strict_types=1);

namespace Nes\Cpu;

use Nes\Bus\Ram;
use Nes\Ppu\Ppu;

class Dma
{
    private bool $isProcessing = false;

    private int $ramAddr = 0x0000;

    private Ram $ram;

    private Ppu $ppu;

    public function __construct(Ram $ram, Ppu $ppu)
    {
        $this->ram = $ram;
        $this->ppu = $ppu;
    }

    public function isDmaProcessing(): bool
    {
        return $this->isProcessing;
    }

    public function runDma(): void
    {
        if (!$this->isProcessing) {
            return;
        }
        for ($i = 0; $i < 0x100; $i = ($i + 1) | 0) {
            $this->ppu->transferSprite($i, $this->ram->read($this->ramAddr + $i));
        }
        $this->isProcessing = false;
    }

    public function write(int $data): void
    {
        $this->ramAddr = $data << 8;
        $this->isProcessing = true;
    }
}
