<?php
namespace Nes\Cpu;

class Addressing
{
    // @codingStandardsIgnoreStart
    const Immediate = 0;
    const ZeroPage = 1;
    const Relative = 2;
    const Implied = 3;
    const Absolute = 4;
    const Accumulator = 5;
    const ZeroPageX = 6;
    const ZeroPageY = 7;
    const AbsoluteX = 8;
    const AbsoluteY = 9;
    const PreIndexedIndirect = 10;
    const PostIndexedIndirect = 11;
    const IndirectAbsolute = 12;
    // @codingStandardsIgnoreEnd
}
