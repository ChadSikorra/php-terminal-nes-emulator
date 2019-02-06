<?php
namespace Nes\Cpu;

class Interrupts
{
    /** @var bool */
    public $nmi = false;
    /** @var bool */
    public $irq = false;

    public function isNmiAssert()
    {
        return $this->nmi;
    }

    public function isIrqAssert()
    {
        return $this->irq;
    }

    public function assertNmi()
    {
        $this->nmi = true;
    }

    public function deassertNmi()
    {
        $this->nmi = false;
    }

    public function assertIrq()
    {
        $this->nmi = true;
    }

    public function deassertIrq()
    {
        $this->nmi = false;
    }
}
