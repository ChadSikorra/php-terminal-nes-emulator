<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
class Dmc extends Channel
{
    private const TABLE = [
        214, 190, 170, 160, 143, 127, 113, 107, 95, 80, 71, 64, 53, 42, 36, 27,
    ];

    public int $sampleAddress = 0;

    public int $sampleLength = 0;

    public int $currentAddress = 0;

    public int $currentLength = 0;

    public int $shiftRegister = 0;

    public int $bitCount = 0;

    public int $tickPeriod = 0;

    public int $tickValue;

    public bool $isLoop;

    public bool $isIrq;

    public function control(int $data): void
    {
        $this->isIrq = ($data & 0x80) === 0x80;
	    $this->isLoop = ($data & 0x40) === 0x40;
	    $this->tickPeriod = self::TABLE[$data & 0x0F];
    }

    public function output(): int
    {
        return 0;
    }

    public function writeValue(int $data): void
    {
        // @todo
    }

    public function writeAddress(int $data): void
    {
        // @todo
    }

    public function writeLength(int $data): void
    {
        // @todo
    }

    public function restart(): void
    {
        // @todo
    }

    public function stepTimer(): void
    {
        // @todo
    }

    public function stepReader(): void
    {
        // @todo
    }

    public function stepShifter(): void
    {
        // @todo
    }
}
