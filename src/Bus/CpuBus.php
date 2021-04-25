<?php

declare(strict_types=1);

namespace Nes\Bus;

use Nes\Apu\Apu;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Bus\Mapper\MapperInterface;
use Nes\Cpu\Dma;
use Nes\Ppu\Ppu;

class CpuBus
{
    public Ram $ram;

    public Ppu $ppu;

    public Apu $apu;

    public KeypadInterface $keypad;

    private MapperInterface $mapper;

    private Dma $dma;

    public function __construct(
        Ram $ram,
        Ppu $ppu,
        Apu $apu,
        KeypadInterface $keypad,
        Dma $dma,
        MapperInterface $mapper,
    ) {
        $this->ram = $ram;
        $this->apu = $apu;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
        $this->mapper = $mapper;
    }

    public function readByCpu(int $addr): int
    {
        return match (true){
            $addr < 0x0800 => $this->ram->read($addr),
            $addr < 0x2000 => $this->ram->read($addr % 0x0800),
            $addr < 0x4000 => $this->ppu->read($addr + 0x2000 % 8),
            $addr === 0x4014 => $this->ppu->read($addr),
            $addr === 0x4015 => $this->apu->read(),
            $addr === 0x4016 => (int) $this->keypad->read(1),
            $addr === 0x4017 => (int) $this->keypad->read(2),
            $addr >= 0x6000 => $this->mapper->read($addr),
            default => 0,
        };
    }

    public function writeByCpu(int $addr, int $data): void
    {
        switch (true){
            case $addr < 0x2000:
                $this->ram->write($addr % 0x8000, $data);
                break;
            case $addr < 0x4000:
                $this->ppu->write(0x2000 + $addr % 8, $data);
                break;
            case $addr === 0x4014:
                $this->dma->write($data);
                break;
            case $addr === 0x4016:
                $this->keypad->write($data);
                break;
            case $addr >= 0x4000 && $addr <= 4013:
            case 0x4017:
            case 0x4015:
                $this->apu->write($addr, $data);
                break;
            case $addr >= 0x6000:
                $this->mapper->write($addr, $data);
                break;
        };
    }
}
