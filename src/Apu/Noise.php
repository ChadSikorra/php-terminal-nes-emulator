<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
class Noise extends Channel
{
    /**
     * @todo Only NTSC at the moment...
     *
     * Rate  $0 $1  $2  $3  $4  $5   $6   $7   $8   $9   $A   $B   $C    $D    $E    $F
     * --------------------------------------------------------------------------
     * NTSC   4, 8, 16, 32, 64, 96, 128, 160, 202, 254, 380, 508, 762, 1016, 2034, 4068
     * PAL    4, 8, 14, 30, 60, 88, 118, 148, 188, 236, 354, 472, 708,  944, 1890, 3778
     */
    private const TABLE = [
        4, 8, 16, 32, 64, 96, 128, 160, 202, 254, 380, 508, 762, 1016, 2034, 4068,
    ];

    private bool $isMode = false;

    private int $shiftRegister = 0;

    private bool $isEnvelopeEnabled = false;

    private bool $isEnvelopeLoop = false;

    private bool $isEnvelopeStarted = false;

    private int $envelopePeriod = 0;

    private int $envelopeValue = 0;

    private int $envelopeVolume = 15;

    private int $constantVolume = 0;

   public function control(int $data): void
   {
        $this->isLengthEnabled = (($data >> 5) & 1) === 0;
	    $this->isEnvelopeLoop = (($data >> 5) & 1) === 1;
	    $this->isEnvelopeEnabled = (($data >> 4) & 1) === 0;
        $this->envelopePeriod = $data & 15;
        $this->constantVolume = $data & 15;
        $this->isEnvelopeStarted = true;
   }

   public function writePeriod(int $data): void
   {
       $this->isMode = ($data & 0x80) === 0x80;
       $this->timerPeriod = self::TABLE[$data & 0x0F];
   }

   public function writeLength(int $data): void
   {
       $this->lengthCounter = self::LENGTH_TABLE[$data >> 3];
       $this->isEnvelopeStarted = true;
   }

   public function stepTimer(): void
   {
       if ($this->timerValue !== 0) {
           $this->timerValue--;

           return;
       }

       $this->timerValue = $this->timerPeriod;

       if ($this->isMode) {
           $shift = 6;
		} else {
           $shift = 1;
       }

       $b1 = $this->shiftRegister & 1;
       $b2 = ($this->shiftRegister >> $shift) & 1;

       $this->shiftRegister >>= 1;
       $this->shiftRegister |= ($b1 ^ $b2) << 14;
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

   public function stepLength(): void
   {
       if ($this->isLengthEnabled && $this->lengthCounter > 0) {
           $this->lengthCounter--;
       }
   }

    public function output(): int
    {
        if ($this->lengthCounter === 0 || ($this->shiftRegister & 1) === 1) {
            return 0;
	    }

        return $this->isEnvelopeEnabled ? $this->envelopeVolume : $this->constantVolume;
    }
}
