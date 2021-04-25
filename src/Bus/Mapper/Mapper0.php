<?php

declare(strict_types=1);

namespace Nes\Bus\Mapper;

use Nes\Bus\Ram;
use Nes\NesFile\NesRom;

class Mapper0 implements MapperInterface
{
    private NesRom $rom;

    private Ram $ram;

    private bool $useMirror;

    public function __construct(NesRom $rom, Ram $ram)
    {
        $this->rom = $rom;
        $this->ram = $ram;
        $this->useMirror = $rom->programRom->size() <= 0x4000;
    }

    public function read(int $addr): int
    {
        return match (true) {
            // Mirror, if prom block number equals 1
            $addr >= 0xC000 => $this->useMirror
                ? $this->rom->programRom->read($addr - 0xC000)
                : $this->rom->programRom->read($addr - 0x8000),
            // ROM
            $addr >= 0x8000 => $this->rom->programRom->read($addr - 0x8000),
        };
    }

    public function write(int $addr, int $data): void
    {
        $this->ram->write($addr, $data);
    }
}
