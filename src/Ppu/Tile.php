<?php

namespace Nes\Ppu;

class Tile
{
    /**
     * @var array<int, array>
     */
    public array $pattern;

    public int $paletteId;

    public int $scrollX;

    public int $scrollY;

    /**
     * @param array<int, array> $pattern
     */
    public function __construct(array $pattern, int $paletteId, int $scrollX, int $scrollY)
    {
        $this->pattern = $pattern;
        $this->paletteId = $paletteId;
        $this->scrollX = $scrollX;
        $this->scrollY = $scrollY;
    }
}
