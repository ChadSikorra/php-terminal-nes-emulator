<?php

declare(strict_types=1);

namespace Nes\NesFile;

use Nes\Bus\Rom;

class NesRom
{
    public bool $isFourScreen;

    public bool $isHorizontalMirror;

    public bool $hasBattery;

    public int $mapper;

    public Rom $programRom;

    /**
     * @var int[]
     */
    public array $characterRom;

    /**
     * @param int[] $characterRom
     */
    public function __construct(
        bool $isHorizontalMirror,
        Rom $programRom,
        array $characterRom,
        bool $hasBattery,
        bool $isFourScreen,
        int $mapper
    ) {
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
        $this->isFourScreen = $isFourScreen;
        $this->hasBattery = $hasBattery;
        $this->mapper = $mapper;
    }
}
