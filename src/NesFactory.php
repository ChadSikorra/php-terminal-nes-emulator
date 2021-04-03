<?php

declare(strict_types=1);

namespace Nes;

use Nes\Bus\CpuBus;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Bus\PpuBus;
use Nes\Bus\Ram;
use Nes\Bus\Rom;
use Nes\Cpu\Cpu;
use Nes\Cpu\Dma;
use Nes\Cpu\Interrupts;
use Nes\NesFile\NesFile;
use Nes\Ppu\Ppu;
use Nes\Ppu\Renderer\RendererInterface;
use Nes\Throttle\PhpThrottle;
use Nes\Throttle\ThrottleInterface;
use RuntimeException;

class NesFactory
{
    public function loadFromRomBinary(
        string $nesRomBinary,
        KeypadInterface $keypad,
        RendererInterface $renderer,
        ?ThrottleInterface $throttle = null,
    ): Nes {
        $nesRom = NesFile::parse($nesRomBinary);

        $ram = new Ram(2048);
        $characterMem = new Ram(0x4000);
        for ($i = 0; $i < count($nesRom->characterRom); ++$i) {
            $characterMem->write($i, $nesRom->characterRom[$i]);
        }

        $programRom = new Rom($nesRom->programRom);
        $ppuBus = new PpuBus($characterMem);
        $interrupts = new Interrupts();
        $ppu = new Ppu($ppuBus, $interrupts, $nesRom->isHorizontalMirror);
        $dma = new Dma($ram, $ppu);
        $cpuBus = new CpuBus(
            $ram,
            $programRom,
            $ppu,
            $keypad,
            $dma,
        );
        $cpu = new Cpu($cpuBus, $interrupts);
        $cpu->reset();

        return new Nes(
            $ppu,
            $dma,
            $cpu,
            $renderer,
            $throttle ?? new PhpThrottle(),
            $keypad,
        );
    }

    public function loadFromFile(
        string $nesRomFilename,
        KeypadInterface $keypad,
        RendererInterface $renderer,
        ?ThrottleInterface $throttle = null,
    ): Nes {
        if (!is_file($nesRomFilename)) {
            throw new RuntimeException('Nes ROM file not found.');
        }
        $nesRomBinary = file_get_contents($nesRomFilename);

        return $this->loadFromRomBinary(
            $nesRomBinary,
            $keypad,
            $renderer,
            $throttle,
        );
    }
}
