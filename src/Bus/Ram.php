<?php

namespace Nes\Bus;

use function array_fill;

class Ram
{
    /**
     * @var int[]
     */
    public array $ram = [];

    public function __construct(int $size)
    {
        $this->ram = array_fill(0, $size, 0);
    }

    public function reset(): void
    {
        $this->ram = array_fill(0, count($this->ram) - 1, 0);
    }

    public function read(int $addr): int
    {
        return $this->ram[$addr];
    }

    public function write(int $addr, int $val): void
    {
        $this->ram[$addr] = $val;
    }
}
