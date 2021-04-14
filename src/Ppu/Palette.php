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

    /**
     * @return int[]
     */
    public function read(): array
    {
        $return = [];

        foreach ($this->paletteRam->ram as $i => $value) {
            // Sprite mirror
            if ((0x10 === $i) || (0x14 === $i) || (0x18 === $i) or (0x1c === $i)) {
                $return[$i] = $this->paletteRam->read($i - 0x10);
            // Background mirror
            } elseif ((0x04 === $i) || (0x08 === $i) || (0x0c === $i)) {
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
        $isSpriteMirror = (0x10 === $mirrorDowned) || (0x14 === $mirrorDowned) || (0x18 === $mirrorDowned) || (0x1c === $mirrorDowned);
        //NOTE: 0x3f10, 0x3f14, 0x3f18, 0x3f1c is mirror of 0x3f00, 0x3f04, 0x3f08, 0x3f0c
        $this->paletteRam->write($isSpriteMirror ? $mirrorDowned - 0x10 : $mirrorDowned, $data);
    }
}
