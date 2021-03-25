<?php

declare(strict_types=1);

namespace Nes\NesFile;

class NesRom
{
    public bool $isHorizontalMirror;

    /**
     * @var int[]
     */
    public array $programRom;

    /**
     * @var int[]
     */
    public array $characterRom;

    /**
     * @param int[] $programRom
     * @param int[] $characterRom
     */
    public function __construct(bool $isHorizontalMirror, array $programRom, array $characterRom)
    {
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
    }
}
