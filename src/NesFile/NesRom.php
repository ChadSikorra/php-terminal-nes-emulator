<?php

declare(strict_types=1);

namespace Nes\NesFile;

use Nes\Bus\Chr;
use Nes\Bus\Rom;

class NesRom
{
    public const MIRROR_HORIZONTAL = 0;

    public const MIRROR_VERTICAL = 1;

    public const MIRROR_SINGLE_SCREEN1 = 2;

    public const MIRROR_SINGLE_SCREEN2 = 3;

    public const MIRROR_FOUR = 4;

    public bool $isFourScreen;

    public int $mirrorMode;

    public bool $hasBattery;

    public int $mapper;

    public Rom $programRom;

    public Chr $characterRom;

    public function __construct(
        int $mirrorMode,
        Rom $programRom,
        Chr $characterRom,
        bool $hasBattery,
        bool $isFourScreen,
        int $mapper
    ) {
        $this->mirrorMode = $mirrorMode;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
        $this->isFourScreen = $isFourScreen;
        $this->hasBattery = $hasBattery;
        $this->mapper = $mapper;
    }

    public function isHorizontalMirror(): bool
    {
        return $this->mirrorMode === self::MIRROR_HORIZONTAL;
    }

    public function isVerticalMirror(): bool
    {
        return $this->mirrorMode === self::MIRROR_HORIZONTAL;
    }
}
