<?php
namespace Nes\Cpu;

use Nes\Bus\Ram;
use Nes\Ppu\Ppu;

class Dma
{
    public $isProcessing;
    public $ramAddr;
    public $ram;
    public $ppu;
    public $addr;
    public $cycle;

    public function __construct(Ram $ram, Ppu $ppu)
    {
        $this->ram = $ram;
        $this->ppu = $ppu;

        $this->isProcessing = false;
        $this->ramAddr = 0x0000;
    }

    /**
     * @return bool
     */
    public function isDmaProcessing()
    {
        return $this->isProcessing;
    }

    public function runDma()
    {
        if (! $this->isProcessing) {
            return;
        }
        $ppu = $this->ppu;
        $ramAddr = $this->ramAddr;
        $ram = $this->ram;
        for ($i = 0; $i < 0x100; ++$i) {
            $ppu->transferSprite($i, $ram->read($ramAddr + $i));
        }
        $this->isProcessing = false;
    }

    /**
     * @param int $data
     */
    public function write($data)
    {
        $this->ramAddr = $data << 8;
        $this->isProcessing = true;
    }
}
