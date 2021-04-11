<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
class Triangle extends Channel
{
    private const TABLE = [
        15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0,
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
    ];

    private int $dutyValue = 0;

    private int $counterPeriod = 0;

    private int $counterValue = 0;

    private bool $isCounterReload = false;

    public function control(int $data): void
    {
        $this->isLengthEnabled = (($data >> 7 ) & 1) === 0;
	    $this->counterPeriod = $data & 0x7F;
    }

    public function writeTimerHigh(int $data): void
    {
        $this->lengthCounter = self::LENGTH_TABLE[$data >> 3];
        $this->timerPeriod = ($this->timerPeriod & 0x00FF) | (($data & 7 ) << 8);
        $this->dutyValue = 0;
    }

    public function writeTimerLow(int $data): void
    {
        $this->timerPeriod = ($this->timerPeriod & 0xFF00) | $data;
    }

    public function stepLength(): void
    {
        if ($this->isLengthEnabled && $this->lengthCounter > 0) {
            $this->lengthCounter--;
        }
    }

    public function stepTimer(): void
    {
        if ($this->timerValue !== 0) {
            $this->timerValue--;

            return;
        }
        $this->timerValue = $this->timerPeriod;

        if ($this->lengthCounter > 0 && $this->counterValue > 0) {
            $this->dutyValue = ($this->dutyValue + 1) % 32;
        }
    }

    public function stepCounter(): void
    {
        if ($this->isCounterReload) {
            $this->counterValue = $this->counterPeriod;
	    } elseif ($this->counterValue > 0) {
            $this->counterValue--;
	    }

        if ($this->isLengthEnabled) {
            $this->isCounterReload = false;
        }
    }

    public function output(): int
    {
        if ($this->lengthCounter === 0 || $this->counterValue === 0) {
            return 0;
        }

        return self::TABLE[$this->dutyValue];
    }
}
