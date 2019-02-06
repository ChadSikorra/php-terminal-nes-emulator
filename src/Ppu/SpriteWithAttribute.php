<?php
namespace Nes\Ppu;

class SpriteWithAttribute
{
    public $sprite;
    public $x;
    public $y;
    public $attribute;
    public $id;

    public static function createDefault()
    {
        return new self(null, 0, 0, 0, 0);
    }

    public function __construct($sprite, $x, $y, $attribute, $id)
    {
        $this->sprite = $sprite;
        $this->x = $x;
        $this->y = $y;
        $this->attribute = $attribute;
        $this->id = $id;
    }
}
