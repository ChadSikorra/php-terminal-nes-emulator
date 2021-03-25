<?php

declare(strict_types=1);

namespace Nes\Cpu;

class Addressing
{
    public const Immediate = 0;

    public const ZeroPage = 1;

    public const Relative = 2;

    public const Implied = 3;

    public const Absolute = 4;

    public const Accumulator = 5;

    public const ZeroPageX = 6;

    public const ZeroPageY = 7;

    public const AbsoluteX = 8;

    public const AbsoluteY = 9;

    public const PreIndexedIndirect = 10;

    public const PostIndexedIndirect = 11;

    public const IndirectAbsolute = 12;
}
