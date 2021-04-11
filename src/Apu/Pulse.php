<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
class Pulse extends Channel
{
    /**
     * Duty | Sequence lookup table | Output waveform
     *   0  |  0 0 0 0 0 0 0 1 	    |  0 1 0 0 0 0 0 0 (12.5%)
     *   1 	|  0 0 0 0 0 0 1 1 	    |  0 1 1 0 0 0 0 0 (25%)
     *   2 	|  0 0 0 0 1 1 1 1 	    |  0 1 1 1 1 0 0 0 (50%)
     *   3 	|  1 1 1 1 1 1 0 0 	    |  1 0 0 1 1 1 1 1 (25% negated)
     *
     * @see https://wiki.nesdev.com/w/index.php/APU_Pulse
     */
    private const OUTPUT_WAVEFORM = [
        [0, 1, 0, 0, 0, 0, 0, 0],
        [0, 1, 1, 0, 0, 0, 0, 0],
        [0, 1, 1, 1, 1, 0, 0, 0],
        [1, 0, 0, 1, 1, 1, 1, 1],
    ];

    private int $channelNumber;

    private int $dutyMode = 0;

    private int $dutyValue = 0;

    private bool $isLengthCounterHalt = false;

    private bool $isEnvelopeStarted = false;

    private bool $isEnvelopeLoop = false;

    private bool $isEnvelopeEnabled = false;

    private int $envelopePeriod = 0;

    private int $envelopeVolume = 15;

    private int $envelopeValue = 0;

    private int $sweepShift = 0;

    private int $sweepPeriod = 0;

    private int $sweepValue = 0;

    private bool $isSweepReload = false;

    private bool $isSweepEnabled = false;

    private bool $isSweepNegate = false;

    private int $constantVolume = 0;

    public function __construct(int $channelNumber)
    {
        $this->channelNumber = $channelNumber;
    }

    public function control(int $data): void
    {
        $this->dutyMode = ($data >> 6) & 3;
        $this->isLengthCounterHalt = (($data >> 5) & 1) === 0;
        $this->isEnvelopeLoop = (($data >> 5) & 1) === 1;
        $this->isEnvelopeEnabled = (($data >> 4) & 1) === 0;
        $this->envelopePeriod = $data & 15;
        $this->constantVolume = $data & 15;
        $this->isEnvelopeStarted = true;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;

        if (!$enabled) {
            $this->lengthCounter = 0;
        }
    }

    public function stepEnvelope(): void
    {
        if ($this->isEnvelopeStarted) {
            $this->envelopeVolume = 15;
            $this->envelopeValue = $this->envelopePeriod;
            $this->isEnvelopeStarted = false;

            return;
	    }

        if ($this->envelopeValue > 0) {
            $this->envelopeValue--;

            return;
	    }

        if ($this->envelopeVolume > 0) {
            $this->envelopeVolume--;
        } elseif ($this->isEnvelopeLoop) {
            $this->envelopeVolume = 15;
        }

        $this->envelopeValue = $this->envelopePeriod;
    }

    public function stepSweep(): void
    {
        if ($this->isSweepReload) {
            if ($this->isSweepEnabled && $this->sweepValue === 0) {
                $this->sweep();
		    }
            $this->sweepValue = $this->sweepPeriod;
		    $this->isSweepReload = false;

		    return;
        }

        if ($this->sweepValue > 0) {
            $this->sweepValue--;

            return;
        }

        if ($this->isSweepEnabled) {
            $this->sweep();
        }

        $this->sweepValue = $this->sweepPeriod;
    }

    public function stepLength(): void
    {
        if ($this->isLengthEnabled && $this->lengthCounter > 0) {
            $this->lengthCounter--;
        }
    }

    public function stepTimer(): void
    {
        if ($this->timerValue === 0) {
            $this->timerValue = $this->timerPeriod;
            $this->dutyValue = ($this->dutyValue + 1) % 8;

            return;
        }

        $this->timerValue--;
    }

    public function writeTimerHigh(int $data): void
    {
        $this->lengthCounter = self::LENGTH_TABLE[$data >> 3];
        $this->timerPeriod = ($this->timerPeriod & 0x00FF) | (($data & 7 ) << 8);
	    $this->isEnvelopeStarted = true;
	    $this->dutyValue = 0;
    }

    public function writeTimerLow(int $data): void
    {
        $this->timerPeriod = ($this->timerPeriod & 0xFF00) | $data;
    }

    public function writeSweep(int $data): void
    {
        $this->isSweepEnabled = (($data >> 7 ) & 1) === 1;
	    $this->sweepPeriod = (($data >> 4) & 7) + 1;
	    $this->isSweepNegate = (($data >> 3) & 1) === 1;
	    $this->sweepShift = $data & 7;
	    $this->isSweepReload = true;
    }

    public function output(): int
    {
        if (
            $this->lengthCounter === 0
            || self::OUTPUT_WAVEFORM[$this->dutyMode][$this->dutyValue] === 0
            || $this->timerPeriod < 8
            || $this->timerPeriod > 0x7FF
        ) {
            return 0;
        }

        return $this->isEnvelopeEnabled ? $this->envelopeVolume : $this->constantVolume;
    }

    private function sweep(): void
    {
        $delta = $this->timerPeriod >> $this->sweepShift;

        if (!$this->isSweepNegate) {
            $this->timerPeriod += $delta;

            return;
        }
        $this->timerPeriod -= $delta;

        if ($this->channelNumber === 1) {
            $this->timerPeriod--;
        }
    }
}
