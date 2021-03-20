<?php

namespace Nes\Ppu;

class RenderingData
{
    /**
     * @var array<int, array>
     */
    public array $palette;

    /**
     * @var Tile[]
     */
    public array $background;

    /**
     * @var SpriteWithAttribute[]
     */
    public array $sprites;

    /**
     * @param array<int, array>     $palette
     * @param Tile[]                $background
     * @param SpriteWithAttribute[] $sprites
     */
    public function __construct(array $palette, array $background, array $sprites)
    {
        $this->palette = $palette;
        $this->background = $background;
        $this->sprites = $sprites;
    }
}
