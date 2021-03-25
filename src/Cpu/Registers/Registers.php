<?php

declare(strict_types=1);

namespace Nes\Cpu\Registers;

class Registers
{
    /**
     * @var int byte
     */
    public int $a;

    /**
     * @var int byte
     */
    public int $x;

    /**
     * @var int byte
     */
    public int $y;

    public Status $p;

    /**
     * @var int word
     */
    public int $sp;

    /**
     * @var int word
     */
    public int $pc;

    public static function getDefault(): Registers
    {
        $instance = new self();
        $instance->a = 0x00;
        $instance->x = 0x00;
        $instance->y = 0x00;
        $instance->p = new Status(
            false,
            false,
            true,
            true,
            false,
            true,
            false,
            false
        );
        $instance->sp = 0x01fd;
        $instance->pc = 0x0000;

        return $instance;
    }
}
