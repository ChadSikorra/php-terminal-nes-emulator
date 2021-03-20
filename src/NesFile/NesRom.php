<?php

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

    public function __construct(bool $isHorizontalMirror, array $programRom, array $characterRom)
    {
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->programRom = $programRom;
        $this->characterRom = $characterRom;
    }
}
