<?php

declare(strict_types=1);

namespace Nes;

use Exception;
use Nes\Bus\CpuBus;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Bus\Keypad\TerminalKeypad;
use Nes\Bus\PpuBus;
use Nes\Bus\Ram;
use Nes\Bus\Rom;
use Nes\Cpu\Cpu;
use Nes\Cpu\Dma;
use Nes\Cpu\Interrupts;
use Nes\NesFile\NesFile;
use Nes\Ppu\Canvas\CanvasInterface;
use Nes\Ppu\Ppu;
use Nes\Ppu\Renderer;
use RuntimeException;

class Nes
{
    public ?Cpu $cpu = null;

    public ?Ppu $ppu = null;

    public ?CpuBus $cpuBus = null;

    public ?Ram $characterMem = null;

    public ?Rom $programRom = null;

    public ?Ram $ram = null;

    public ?PpuBus $ppuBus = null;

    public ?Renderer $renderer = null;

    public ?KeypadInterface $keypad = null;

    public ?Dma $dma = null;

    public ?Interrupts $interrupts = null;

    public ?Debugger $debugger = null;

    /**
     * @var int[]
     */
    public array $frame;

    public function __construct(CanvasInterface $canvas)
    {
        $this->frame = [];
        $this->renderer = new Renderer($canvas);
    }

    //
    // Memory map
    /*
    | addr           |  description               |   mirror       |
    +----------------+----------------------------+----------------+
    | 0x0000-0x07FF  |  RAM                       |                |
    | 0x0800-0x1FFF  |  reserve                   | 0x0000-0x07FF  |
    | 0x2000-0x2007  |  I/O(PPU)                  |                |
    | 0x2008-0x3FFF  |  reserve                   | 0x2000-0x2007  |
    | 0x4000-0x401F  |  I/O(APU, etc)             |                |
    | 0x4020-0x5FFF  |  ex RAM                    |                |
    | 0x6000-0x7FFF  |  battery backup RAM        |                |
    | 0x8000-0xBFFF  |  program ROM LOW           |                |
    | 0xC000-0xFFFF  |  program ROM HIGH          |                |
    */

    /**
     * @throws Exception
     */
    public function load(string $nesRomFilename): void
    {
        if (!is_file($nesRomFilename)) {
            throw new RuntimeException('Nes ROM file not found.');
        }
        $nesRomBinary = file_get_contents($nesRomFilename);
        $nesRom = NesFile::parse($nesRomBinary);

        $this->keypad = new TerminalKeypad();
        $this->ram = new Ram(2048);
        $this->characterMem = new Ram(0x4000);
        for ($i = 0; $i < count($nesRom->characterRom); ++$i) {
            $this->characterMem->write($i, $nesRom->characterRom[$i]);
        }
        $this->programRom = new Rom($nesRom->programRom);
        $this->ppuBus = new PpuBus($this->characterMem);
        $this->interrupts = new Interrupts();
        $this->ppu = new Ppu($this->ppuBus, $this->interrupts, $nesRom->isHorizontalMirror);
        $this->dma = new Dma($this->ram, $this->ppu);
        $this->cpuBus = new CpuBus(
            $this->ram,
            $this->programRom,
            $this->ppu,
            $this->keypad,
            $this->dma
        );
        $this->cpu = new Cpu($this->cpuBus, $this->interrupts);
        $this->cpu->reset();
    }

    /**
     * @throws Exception
     */
    public function frame(): void
    {
        while (true) {
            $cycle = 0;
            if ($this->dma->isDmaProcessing()) {
                $this->dma->runDma();
                $cycle = 514;
            }
            $cycle += $this->cpu->run();
            $renderingData = $this->ppu->run($cycle * 3);
            if ($renderingData) {
                $this->cpu->bus->keypad->fetch();
                $this->renderer->render($renderingData);

                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function start(): void
    {
        do {
            $this->frame();
        } while (true);
    }

    public function close(): void
    {
    }
}
