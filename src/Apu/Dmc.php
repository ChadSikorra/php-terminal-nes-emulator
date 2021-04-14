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

    public int $value = 0;

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
        $this->value = $data & 0x7F;
    }

    public function writeAddress(int $data): void
    {
        $this->sampleAddress = 0xC000 | ($data << 6);
    }

    public function writeLength(int $data): void
    {
        $this->sampleLength = ($data << 4) | 1;
    }

    public function restart(): void
    {
        $this->currentAddress = $this->sampleAddress;
        $this->currentLength = $this->sampleLength;
    }

    public function stepTimer(): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        $this->stepReader();

        if ($this->tickValue === 0) {
            $this->tickValue = $this->tickPeriod;
            $this->stepShifter();
        } else {
            $this->tickValue--;
        }
    }

    public function stepReader(): void
    {
        if ($this->currentLength > 0 && $this->bitCount == 0) {
            // @todo Need access to the CPU here. No concept of stall for the current CPU implementation either.
            // $this->cpu->stall += 4;
		    // $this->shiftRegister = $this->cpu->read($this->currentAddress);
		    $this->bitCount = 8;

		    $this->currentAddress++;
            if ($this->currentAddress == 0) {
                $this->currentAddress = 0x8000;
            }
            $this->currentLength--;

            if ($this->currentLength == 0 && $this->isLoop) {
                $this->restart();
            }
	    }
    }

    public function stepShifter(): void
    {
        if ($this->bitCount === 0) {
            return;
	    }

        if (($this->shiftRegister & 1) === 1) {
            if ($this->value <= 125) {
                $this->value += 2;
		    }
        } else {
            if ($this->value >= 2) {
                $this->value -= 2;
		    }
        }

        $this->shiftRegister >>= 1;
	    $this->bitCount--;
    }
}
