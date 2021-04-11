<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
abstract class Channel implements ChannelInterface
{
    /**
     * Bits 7 -3 of $400F
     *
     *      |  0   1   2   3   4   5   6   7    8   9   A   B   C   D   E   F
     * -----+----------------------------------------------------------------
     * 00-0F  10,254, 20,  2, 40,  4, 80,  6, 160,  8, 60, 10, 14, 12, 26, 14,
     * 10-1F  12, 16, 24, 18, 48, 20, 96, 22, 192, 24, 72, 26, 16, 28, 32, 30
     *
     * @see https://wiki.nesdev.com/w/index.php/APU_Length_Counter
     */
    protected const LENGTH_TABLE  = [
        10, 254, 20,  2, 40,  4, 80,  6, 160,  8, 60, 10, 14, 12, 26, 14,
        12,  16, 24, 18, 48, 20, 96, 22, 192, 24, 72, 26, 16, 28, 32, 30,
    ];

    protected bool $enabled = false;

    protected int $lengthCounter = 0;

    protected bool $isLengthEnabled = false;

    protected int $timerPeriod = 0;

    protected int $timerValue = 0;

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;

        if (!$enabled) {
            $this->lengthCounter = 0;
        }
    }

    public function isEnabled(): bool
    {
        return $this->lengthCounter > 0;
    }
}
