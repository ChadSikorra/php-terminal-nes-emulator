<?php
namespace Nes\Cpu;

class OpCodeProps
{
    public $fullName;
    public $baseType;
    public $mode;
    public $cycle;

    public function __construct($fullName, $baseType, $mode, $cycle)
    {
        $this->fullName = $fullName;
        $this->baseType = $baseType;
        $this->mode = $mode;
        $this->cycle = $cycle;
    }
}
