<?php

declare(strict_types=1);

namespace Nes\Cpu;

class Interrupts
{
    public bool $nmi = false;

    public bool $irq = false;

    public function isNmiAssert(): bool
    {
        return $this->nmi;
    }

    public function isIrqAssert(): bool
    {
        return $this->irq;
    }

    public function assertNmi(): void
    {
        $this->nmi = true;
    }

    public function deassertNmi(): void
    {
        $this->nmi = false;
    }

    public function assertIrq(): void
    {
        $this->nmi = true;
    }

    public function deassertIrq(): void
    {
        $this->nmi = false;
    }
}
