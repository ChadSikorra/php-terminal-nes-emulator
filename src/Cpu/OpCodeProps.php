<?php
namespace Nes\Cpu;

class OpCodeProps
{
    public $fullName;
    public $baseType;
    public $mode;
    public $cycle;

    public function __construct(string $fullName, int $baseType, int $mode, int $cycle)
    {
        $this->fullName = $fullName;
        $this->baseType = $baseType;
        $this->mode = $mode;
        $this->cycle = $cycle;
    }
}
