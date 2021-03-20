<?php

namespace Nes\Bus;

use Nes\Cpu\Dma;
use Nes\Ppu\Ppu;

class CpuBus
{
    public Ram $ram;

    public Rom $programRom;

    public Ppu $ppu;

    public Keypad $keypad;

    public Dma $dma;

    private bool $use_mirror;

    public function __construct(Ram $ram, Rom $programRom, Ppu $ppu, Keypad $keypad, Dma $dma)
    {
        $this->ram = $ram;
        $this->programRom = $programRom;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
        $this->use_mirror = $this->programRom->size() <= 0x4000;
    }

    public function readByCpu(int $addr): int
    {
        if ($addr >= 0xC000) {
            // Mirror, if prom block number equals 1
            if ($this->use_mirror) {
                return $this->programRom->read($addr - 0xC000);
            }

            return $this->programRom->read($addr - 0x8000);
        }
        if ($addr >= 0x8000) {
            // ROM
            return $this->programRom->read($addr - 0x8000);
        }
        if ($addr < 0x0800) {
            return $this->ram->read($addr);
        }
        if ($addr < 0x2000) {
            // mirror
            return $this->ram->read($addr - 0x0800);
        }
        if ($addr < 0x4000) {
            // mirror
            return $this->ppu->read(($addr - 0x2000) % 8);
        }
        if (0x4016 === $addr) {
            // TODO Add 2P
            return (int) $this->keypad->read();
        }

        return 0;
    }

    public function writeByCpu(int $addr, int $data): void
    {
        if ($addr < 0x0800) {
            // RAM
            $this->ram->write($addr, $data);
        } elseif ($addr < 0x2000) {
            // mirror
            $this->ram->write($addr - 0x0800, $data);
        } elseif ($addr < 0x2008) {
            // PPU
            $this->ppu->write($addr - 0x2000, $data);
        } elseif ($addr >= 0x4000 && $addr < 0x4020) {
            if (0x4014 === $addr) {
                $this->dma->write($data);
            } elseif (0x4016 === $addr) {
                // TODO Add 2P
                $this->keypad->write($data);
            } else {
                // APU
                return;
            }
        }
    }
}
