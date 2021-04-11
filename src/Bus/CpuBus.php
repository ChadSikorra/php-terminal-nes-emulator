<?php

declare(strict_types=1);

namespace Nes\Bus;

use Nes\Apu\Apu;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Cpu\Dma;
use Nes\Ppu\Ppu;

class CpuBus
{
    public Ram $ram;

    public Rom $programRom;

    public Ppu $ppu;

    public Apu $apu;

    public KeypadInterface $keypad;

    private Dma $dma;

    private bool $use_mirror;

    public function __construct(
        Ram $ram,
        Rom $programRom,
        Ppu $ppu,
        Apu $apu,
        KeypadInterface $keypad,
        Dma $dma
    ) {
        $this->ram = $ram;
        $this->programRom = $programRom;
        $this->apu = $apu;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
        $this->use_mirror = $this->programRom->size() <= 0x4000;
    }

    public function readByCpu(int $addr): int
    {
        return match (true){
            $addr === 0x4015 => $this->apu->read(),
            $addr === 0x4016 => (int) $this->keypad->read(1),
            $addr === 0x4017 => (int) $this->keypad->read(2),
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
            default => 0,
        };
    }

    public function writeByCpu(int $addr, int $data): void
    {
        if ($addr < 0x0800) {
            // RAM
            $this->ram->write($addr, $data);

            return;
        } elseif ($addr < 0x2000) {
            // mirror
            $this->ram->write($addr - 0x0800, $data);

            return;
        } elseif ($addr < 0x2008) {
            // PPU
            $this->ppu->write($addr - 0x2000, $data);

            return;
        }

        switch ($addr) {
            case 0x4014:
                $this->dma->write($data);
                break;
            case 0x4016:
                $this->keypad->write($data);
                break;
            case $addr >= 0x4000 && $addr <= 4013:
            case 0x4015:
            case 0x4017:
                $this->apu->write($addr, $data);
                break;
        }
    }
}
