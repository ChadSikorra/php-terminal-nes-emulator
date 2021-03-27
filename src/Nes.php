<?php

declare(strict_types=1);

namespace Nes;

use Exception;
use Nes\Bus\Keypad\KeypadInterface;
use Nes\Cpu\Cpu;
use Nes\Cpu\Dma;
use Nes\Ppu\Ppu;
use Nes\Ppu\Renderer;
use Nes\Throttle\ThrottleInterface;

class Nes
{
    private Cpu $cpu;

    private Ppu $ppu;

    private Renderer $renderer;

    private KeypadInterface $keypad;

    private Dma $dma;

    private ThrottleInterface $throttle;

    /**
     * @var int[]
     */
    public array $frame;

    public function __construct(
        Ppu $ppu,
        Dma $dma,
        Cpu $cpu,
        Renderer $renderer,
        ThrottleInterface $throttle,
        KeypadInterface $keypad,
    ) {
        $this->ppu = $ppu;
        $this->dma = $dma;
        $this->cpu = $cpu;
        $this->keypad = $keypad;
        $this->frame = [];
        $this->renderer = $renderer;
        $this->throttle = $throttle;
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
                $this->keypad->fetch();
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
            $this->throttle->throttle(fn() => $this->frame());
        } while (true);
    }
}
