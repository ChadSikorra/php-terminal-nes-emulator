<?php

declare(strict_types=1);

namespace Nes;

use Nes\Apu\Apu;
use Nes\Bus\CpuBus;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Bus\Mapper\MapperFactory;
use Nes\Bus\PpuBus;
use Nes\Bus\Ram;
use Nes\Cpu\Cpu;
use Nes\Cpu\Dma;
use Nes\Cpu\Interrupts;
use Nes\NesFile\NesFileParser;
use Nes\Ppu\Ppu;
use Nes\Ppu\Renderer\RendererInterface;
use Nes\Throttle\PhpThrottle;
use Nes\Throttle\ThrottleInterface;
use RuntimeException;

class NesFactory
{
    private NesFileParser $nesFileParser;

    private MapperFactory $mapperFactory;

    public function __construct(
        ?NesFileParser $nesFileParser = null,
        ?MapperFactory $mapperFactory = null,
    ) {
        $this->nesFileParser = $nesFileParser ?? new NesFileParser();
        $this->mapperFactory = $mapperFactory ?? new MapperFactory();
    }

    public function loadFromRomBinary(
        string $nesRomBinary,
        KeypadInterface $keypad,
        RendererInterface $renderer,
        ?ThrottleInterface $throttle = null,
    ): Nes {
        $nesRom = $this->nesFileParser->parse($nesRomBinary);

        $ram = new Ram(2048);
        $characterMem = new Ram(0x4000);
        for ($i = 0; $i < $nesRom->characterRom->size(); ++$i) {
            $characterMem->write($i, $nesRom->characterRom->read($i));
        }

        $ppuBus = new PpuBus($characterMem);
        $interrupts = new Interrupts();
        $ppu = new Ppu($ppuBus, $interrupts, $nesRom->isHorizontalMirror);
        $apu = new Apu();
        $dma = new Dma($ram, $ppu);
        $cpuBus = new CpuBus(
            $ram,
            $ppu,
            $apu,
            $keypad,
            $dma,
            $this->mapperFactory->makeMapper($nesRom, $ram)
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
