<?php
namespace Nes\Bus;

use Nes\Cpu\Dma;
use Nes\Debugger;
use Nes\Ppu\Ppu;

class CpuBus
{
    public $ram;
    public $programRom;
    public $ppu;
    public $keypad;
    public $dma;
    public $use_mirror = false;

    public function __construct(Ram $ram, Rom $programRom, Ppu $ppu, Keypad $keypad, Dma $dma)
    {
        $this->ram = $ram;
        $this->programRom = $programRom;
        $this->ppu = $ppu;
        $this->keypad = $keypad;
        $this->dma = $dma;
        $this->use_mirror = $this->programRom->size() <= 0x4000;
    }

    public function readByCpu($addr)
    {
        if ($addr >= 0xC000) {
            // Mirror, if prom block number equals 1
            if ($this->use_mirror) {
                return $this->programRom->rom[$addr - 0xC000];
            }
            return $this->programRom->rom[$addr - 0x8000];
        } elseif ($addr >= 0x8000) {
            // ROM
            return $this->programRom->rom[$addr - 0x8000];
        } elseif ($addr < 0x0800) {
            return $this->ram->read($addr);
        } elseif ($addr < 0x2000) {
            // mirror
            return $this->ram->read($addr - 0x0800);
        } elseif ($addr < 0x4000) {
            // mirror
            $data = $this->ppu->read(($addr - 0x2000) % 8);
            return $data;
        } elseif ($addr === 0x4016) {
            // TODO Add 2P
            return $this->keypad->read();
        }
        return false;
    }

    public function writeByCpu(int $addr, int $data)
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
            if ($addr === 0x4014) {
                $this->dma->write($data);
            } elseif ($addr === 0x4016) {
                // TODO Add 2P
                $this->keypad->write($data);
            } else {
                // APU
                return false;
            }
        }
    }
}
