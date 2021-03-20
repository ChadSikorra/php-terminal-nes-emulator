<?php

namespace Nes\Bus;

use RuntimeException;

class Rom
{
    /**
     * @var int[]
     */
    public array $rom = [];

    private int $size;

    /**
     * @param int[] $data
     */
    public function __construct(array $data)
    {
        $this->rom = $data;
        $this->size = count($data);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function read(int $addr): int
    {
        if (!isset($this->rom[$addr])) {
            throw new RuntimeException(sprintf('Invalid address on rom read. Address: 0x%s Rom: 0x0000 - 0x%s', dechex($addr), dechex($this->size)));
        }

        return $this->rom[$addr];
    }
}
