<?php

declare(strict_types=1);

namespace Nes\Apu;

/**
 * Adapted from APU implementation by Michael Fogleman: https://github.com/fogleman/nes
 */
class Apu
{
    private const REG_PULSE1_CONTROL = 0x4000;

    private const REG_PULSE1_SWEEP = 0x4001;

    private const REG_PULSE1_LOW = 0x4002;

    private const REG_PULSE1_HIGH = 0x4003;

    private const REG_PULSE2_CONTROL = 0x4004;

    private const REG_PULSE2_SWEEP = 0x4005;

    private const REG_PULSE2_LOW = 0x4006;

    private const REG_PULSE2_HIGH = 0x4007;

    private const REG_TRIANGLE_CONTROL = 0x4008;

    private const REG_TRIANGLE_LOW = 0x400A;

    private const REG_TRIANGLE_HIGH = 0x400B;

    private const REG_NOISE_CONTROL = 0x400C;

    private const REG_NOISE_ADDRESS = 0x400E;

    private const REG_NOISE_LENGTH = 0x400F;

    private const REG_DMC_CONTROL = 0x4010;

    private const REG_DMC_COUNTER = 0x4011;

    private const REG_DMC_ADDRESS = 0x4012;

    private const REG_DMC_LENGTH = 0x4013;

    private const REG_STATUS = 0x4015;

    private const REG_FRAME_COUNTER = 0x4017;

    private Pulse $pulse1;

    private Pulse $pulse2;

    private Triangle $triangle;

    private Noise $noise;

    private Dmc $dmc;

    private bool $isFrameIRQ = false;

    private int $framePeriod;

    private array $pulseTable = [0 => 0];

    private array $tndTable = [0 => 0];

    public function __construct()
    {
        $this->pulse1 = new Pulse(1);
        $this->pulse2 = new Pulse(2);
        $this->triangle = new Triangle();
        $this->noise = new Noise();
        $this->dmc = new Dmc();

        # Init the APU pulse and TND (Triangle, Noise, DMC) lookup tables
        # Part of the APU mixer process: https://wiki.nesdev.com/w/index.php/APU_Mixer
        for ($i = 1; $i < 31; $i++) {
            $this->pulseTable[$i] = 95.52 / (8128.0 / (float)$i + 100);
	    }
        for ($i = 1; $i < 203; $i++) {
            $this->tndTable[$i] = 163.67 / (24329.0 / (float)$i + 100);
        }
    }

    public function read(): int
    {
        $status = 0;

        if ($this->pulse1->isEnabled()) {
            $status |= 1;
        }
        if ($this->pulse2->isEnabled()) {
            $status |= 2;
        }
        if ($this->triangle->isEnabled()) {
            $status |= 4;
        }
        if ($this->noise->isEnabled()) {
            $status |= 8;
        }
        if ($this->dmc->isEnabled()) {
            $status |= 16;
        }

        return $status;
    }

    public function write(int $addr, int $data): void
    {
        switch ($addr) {
            case self::REG_PULSE1_CONTROL:
                $this->pulse1->control($data);
                break;
            case self::REG_PULSE1_SWEEP:
                $this->pulse1->writeSweep($data);
                break;
            case self::REG_PULSE1_LOW:
                $this->pulse1->writeTimerLow($data);
                break;
            case self::REG_PULSE1_HIGH:
                $this->pulse1->writeTimerHigh($data);
                break;
            case self::REG_PULSE2_CONTROL:
                $this->pulse2->control($data);
                break;
            case self::REG_PULSE2_SWEEP:
                $this->pulse2->writeSweep($data);
                break;
            case self::REG_PULSE2_LOW:
                $this->pulse2->writeTimerLow($data);
                break;
            case self::REG_PULSE2_HIGH:
                $this->pulse2->writeTimerHigh($data);
                break;
            case self::REG_TRIANGLE_CONTROL:
                $this->triangle->control($data);
                break;
            case self::REG_TRIANGLE_LOW:
                $this->triangle->writeTimerLow($data);
                break;
            case self::REG_TRIANGLE_HIGH:
                $this->triangle->writeTimerHigh($data);
                break;
            case self::REG_NOISE_CONTROL:
                $this->noise->control($data);
                break;
            case self::REG_NOISE_ADDRESS:
                $this->noise->writePeriod($data);
                break;
            case self::REG_NOISE_LENGTH:
                $this->noise->writeLength($data);
                break;
            case self::REG_DMC_CONTROL:
                $this->dmc->control($data);
                break;
            case self::REG_DMC_COUNTER:
                $this->dmc->writeValue($data);
                break;
            case self::REG_DMC_ADDRESS:
                $this->dmc->writeAddress($data);
                break;
            case self::REG_DMC_LENGTH:
                $this->dmc->writeLength($data);
                break;
            case self::REG_STATUS:
                $this->control($data);
                break;
            case self::REG_FRAME_COUNTER:
                $this->frameCounter($data);
                break;
        }
    }

    public function output()
    {
        $pulse1 = $this->pulse1->output();
	    $pulse2 = $this->pulse2->output();
	    $triangle = $this->triangle->output();
	    $noise = $this->noise->output();
	    $dmc = $this->dmc->output();

	    $pulseOut = $this->pulseTable[$pulse1 + $pulse2];
	    $tndOut = $this->tndTable[(3 * $triangle) + (2 * $noise) + $dmc];

	    return $pulseOut + $tndOut;
    }

    private function control(int $data): void
    {
        $this->pulse1->setEnabled(($data & 1) === 1);
        $this->pulse2->setEnabled(($data & 2) === 2);
        $this->triangle->setEnabled(($data & 4) === 4);
        $this->noise->setEnabled(($data & 8) === 8);
        $this->dmc->setEnabled(($data & 16) === 16);
    }

    private function frameCounter(int $data): void
    {
        $mode = (($data >> 7 ) & 1);
        $this->framePeriod = 4 + $mode;
	    $this->isFrameIRQ = ((($data >> 6) & 1) === 0);

	    // 5-step sequence when bit 7 is set
	    if ($mode === 1) {
            $this->stepEnvelope();
            $this->stepSweep();
            $this->stepLength();
        }
    }

    private function stepEnvelope()
    {
        $this->pulse1->stepEnvelope();
        $this->pulse2->stepEnvelope();
        $this->triangle->stepCounter();
        $this->noise->stepEnvelope();
    }

    private function stepSweep()
    {
        $this->pulse1->stepSweep();
        $this->pulse2->stepSweep();
    }

    private function stepLength()
    {
        $this->pulse1->stepLength();
        $this->pulse2->stepLength();
        $this->triangle->stepLength();
        $this->noise->stepLength();
    }
}
