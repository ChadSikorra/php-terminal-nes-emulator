<?php

declare(strict_types=1);

namespace Nes\Bus;

use Nes\Bus\Keypad\KeypadInterface;
use Nes\Cpu\Dma;
use Nes\Ppu\Ppu;

class CpuBus
{
    public Ram $ram;

    public Rom $programRom;

    public Ppu $ppu;

    public KeypadInterface $keypad;

    public Dma $dma;

    private bool $use_mirror;

    public function __construct(
        Ram $ram,
        Rom $programRom,
        Ppu $ppu,
        KeypadInterface $keypad,
        Dma $dma
    ) {
        $this->ram = $ram;
        $this->programRom = $programRom;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
        $this->use_mirror = $this->programRom->size() <= 0x4000;
    }

    public function readByCpu(int $addr): int
    {
        return match (true){
            // Mirror, if prom block number equals 1
            $addr >= 0xC000 => $this->use_mirror
                ? $this->programRom->read($addr - 0xC000)
                : $this->programRom->read($addr - 0x8000),
            // ROM
            $addr >= 0x8000 => $this->programRom->read($addr - 0x8000),
            $addr < 0x0800 => $this->ram->read($addr),
            // mirror
            $addr < 0x2000 => $this->ram->read($addr - 0x0800),
            // mirror
            $addr < 0x4000 => $this->ppu->read(($addr - 0x2000) % 8),
            // @TODO Add 2P
            $addr === 0x4016 => (int) $this->keypad->read(),
            default => 0,
        };
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
