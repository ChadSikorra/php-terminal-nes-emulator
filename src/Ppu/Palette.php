<?php

declare(strict_types=1);

namespace Nes\Ppu;

use Nes\Bus\Ram;

class Palette
{
    public Ram $paletteRam;

    public function __construct()
    {
        $this->paletteRam = new Ram(0x20);
    }

    public function isSpriteMirror(int $addr): bool
    {
        return (0x10 === $addr) or (0x14 === $addr) or (0x18 === $addr) or (0x1c === $addr);
    }

    public function isBackgroundMirror(int $addr): bool
    {
        return (0x04 === $addr) or (0x08 === $addr) or (0x0c === $addr);
    }

    /**
     * @return int[]
     */
    public function read(): array
    {
        $return = [];

        foreach ($this->paletteRam->ram as $i => $value) {
            if ($this->isSpriteMirror($i)) {
                $return[$i] = $this->paletteRam->read($i - 0x10);
            } elseif ($this->isBackgroundMirror($i)) {
                $return[$i] = $this->paletteRam->read(0x00);
            } else {
                $return[$i] = $value;
            }
        }

        return $return;
    }

    public function write(int $addr, int $data): void
    {
        $mirrorDowned = (($addr & 0xFF) % 0x20);
        //NOTE: 0x3f10, 0x3f14, 0x3f18, 0x3f1c is mirror of 0x3f00, 0x3f04, 0x3f08, 0x3f0c
        $this->paletteRam->write($this->isSpriteMirror($mirrorDowned) ? $mirrorDowned - 0x10 : $mirrorDowned, $data);
    }
}
