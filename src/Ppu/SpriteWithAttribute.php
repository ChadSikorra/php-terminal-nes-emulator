<?php

namespace Nes\Ppu;

class SpriteWithAttribute
{
    /**
     * @var array<int[]>
     */
    public array $sprite;

    public int $x;

    public int $y;

    public int $attribute;

    public int $id;

    /**
     * @param array<int[]> $sprite
     */
    public function __construct(array $sprite, int $x, int $y, int $attribute, int $id)
    {
        $this->sprite = $sprite;
        $this->x = $x;
        $this->y = $y;
        $this->attribute = $attribute;
        $this->id = $id;
    }
}
