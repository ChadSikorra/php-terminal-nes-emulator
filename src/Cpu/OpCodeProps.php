<?php

namespace Nes\Cpu;

class OpCodeProps
{
    public string $fullName;

    public int $baseType;

    public int $mode;

    public int $cycle;

    public function __construct(string $fullName, int $baseType, int $mode, int $cycle)
    {
        $this->fullName = $fullName;
        $this->baseType = $baseType;
        $this->mode = $mode;
        $this->cycle = $cycle;
    }
}
