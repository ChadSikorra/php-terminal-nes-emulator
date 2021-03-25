<?php

declare(strict_types=1);

namespace Nes\Cpu\Registers;

class Status
{
    public bool $negative;

    public bool $overflow;

    public bool $reserved;

    public bool $break_mode;

    public bool $decimal_mode;

    public bool $interrupt;

    public bool $zero;

    public bool $carry;

    public function __construct(
        bool $negative,
        bool $overflow,
        bool $reserved,
        bool $break_mode,
        bool $decimal_mode,
        bool $interrupt,
        bool $zero,
        bool $carry
    ) {
        $this->negative = $negative;
        $this->overflow = $overflow;
        $this->reserved = $reserved;
        $this->break_mode = $break_mode;
        $this->decimal_mode = $decimal_mode;
        $this->interrupt = $interrupt;
        $this->zero = $zero;
        $this->carry = $carry;
    }
}
