<?php
namespace Nes\Ppu;

use Nes\Ppu\Canvas\CanvasInterface;

class Renderer
{
    /** @var int[] */
    public $frameBuffer = [];
    /** @var \Nes\Ppu\Tile[] */
    public $background;
    /** @var int */
    public $serial = 0;
    /** @var \Nes\Ppu\Canvas\CanvasInterface */
    public $canvas;

    const COLORS = [
        0x808080, 0x003DA6, 0x0012B0, 0x440096,
        0xA1005E, 0xC70028, 0xBA0600, 0x8C1700,
        0x5C2F00, 0x104500, 0x054A00, 0x00472E,
        0x004166, 0x000000, 0x050505, 0x050505,
        0xC7C7C7, 0x0077FF, 0x2155FF, 0x8237FA,
        0xEB2FB5, 0xFF2950, 0xFF2200, 0xD63200,
        0xC46200, 0x358000, 0x058F00, 0x008A55,
        0x0099CC, 0x212121, 0x090909, 0x090909,
        0xFFFFFF, 0x0FD7FF, 0x69A2FF, 0xD480FF,
        0xFF45F3, 0xFF618B, 0xFF8833, 0xFF9C12,
        0xFABC20, 0x9FE30E, 0x2BF035, 0x0CF0A4,
        0x05FBFF, 0x5E5E5E, 0x0D0D0D, 0x0D0D0D,
        0xFFFFFF, 0xA6FCFF, 0xB3ECFF, 0xDAABEB,
        0xFFA8F9, 0xFFABB3, 0xFFD2B0, 0xFFEFA6,
        0xFFF79C, 0xD7E895, 0xA6EDAF, 0xA2F2DA,
        0x99FFFC, 0xDDDDDD, 0x111111, 0x111111,
    ];

    public function __construct(CanvasInterface $canvas)
    {
        // 256 x 240
        for ($i = 0; $i < 0xffff; $i++) {
            $this->frameBuffer[$i] = 0;
        }

        $this->canvas = $canvas;
    }

    /**
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function shouldPixelHide($x, $y)
    {
        $tileX = (int)($x / 8);
        $tileY = (int)($y / 8);
        $backgroundIndex = $tileY * 33 + $tileX;
        $sprite = $this->background[$backgroundIndex] && $this->background[$backgroundIndex]->pattern;
        if (! $sprite) {
            return true;
        }
        // NOTE: If background pixel is not transparent, we need to hide sprite.
        return !(($sprite[$y % 8] && $sprite[$y % 8][$x % 8] % 4) === 0);
    }

    /**
     * @param RenderingData $data
     */
    public function render($data)
    {
        if ($data->background or $data->sprites) {
            $paletteColorsMap = [];
            $colors = self::COLORS;
            foreach ($data->palette as $key => $colorIndex) {
                $paletteColorsMap[$key] = $colors[$colorIndex];
            }
        }
        if ($data->background) {
            $this->renderBackground($data->background, $paletteColorsMap);
        }
        if ($data->sprites) {
            $this->renderSprites($data->sprites, $paletteColorsMap);
        }

        $this->canvas->draw($this->frameBuffer);
    }

    /**
     * @param \Nes\Ppu\Tile[] $background
     * @param int[] $paletteColorsMap
     */
    public function renderBackground($background, $paletteColorsMap)
    {
        $count_background = count($background);
        $this->background = $background;
        for ($i = 0; $i < $count_background; ++$i) {
            $x = ($i % 33) * 8;
            $y = (int)($i / 33) * 8;
            $this->renderTile($background[$i], $x, $y, $paletteColorsMap);
        }
    }

    /**
     * @param \Nes\Ppu\SpriteWithAttribute[] $sprites
     * @param int[] $paletteColorsMap
     */
    public function renderSprites($sprites, $paletteColorsMap)
    {
        foreach ($sprites as $sprite) {
            if ($sprite) {
                $this->renderSprite($sprite, $paletteColorsMap);
            }
        }
    }

    /**
     * @param \Nes\Ppu\Tile $tile
     * @param int $tileX
     * @param int $tileY
     * @param int[] $paletteColorsMap
     */
    public function renderTile($tile, $tileX, $tileY, $paletteColorsMap)
    {
        //{ sprite, paletteId, scrollX, scrollY }: Tile
        $offsetX = $tileX - ($tile->scrollX % 8);
        $offsetY = $tileY - ($tile->scrollY % 8);
        $paletteIndexBase = $tile->paletteId * 4;
        $colorMap = [];
        foreach ($paletteColorsMap as $key => $value) {
            $colorMap[$key - $paletteIndexBase] = $value;
        }
        $frameBuffer = &$this->frameBuffer;
        if ($offsetX >= 0 && 0xFF >= ($offsetX + 7)) {
            for ($i = 0; $i < 8; ++$i) {
                $y = $i + $offsetY;
                if ($y >= 0 && $y < 224) {
                    $frameBufferOffsetY = $y * 0x100;
                    $pattern = $tile->pattern[$i];
                    $frameBufferOffset = $offsetX + $frameBufferOffsetY;
                    $frameBuffer[$frameBufferOffset] = $colorMap[$pattern[0]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[1]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[2]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[3]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[4]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[5]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[6]];
                    $frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[7]];
                }
            }
        } else {
            for ($i = 0; $i < 8; ++$i) {
                $y = $i + $offsetY;
                if ($y >= 0 && $y < 224) {
                    $frameBufferOffsetY = $y * 0x100;
                    $pattern = $tile->pattern[$i];
                    for ($j = 0; $j < 8; ++$j) {
                        $x = $j + $offsetX;
                        if ($x >= 0 && 0xFF >= $x) {
                            $frameBuffer[$x + $frameBufferOffsetY] = $paletteColorsMap[$paletteIndexBase + $pattern[$j]];
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \Nes\Ppu\SpriteWithAttribute $sprite
     * @param int[] $paletteColorsMap
     */
    public function renderSprite($sprite, $paletteColorsMap)
    {
        $isVerticalReverse = (bool)($sprite->attribute & 0x80);
        $isHorizontalReverse = (bool)($sprite->attribute & 0x40);
        $isLowPriority = (bool)($sprite->attribute & 0x20);
        $paletteId = $sprite->attribute & 0x03;
        $paletteIndexBase = $paletteId * 4 + 0x10;
        $ordinalTable = [0, 1, 2, 3, 4, 5, 6, 7];
        $reverseTable = [7, 6, 5, 4, 3, 2, 1, 0];
        $verticalTable = $isVerticalReverse ? $reverseTable : $ordinalTable;
        $horizontalTable = $isHorizontalReverse ? $reverseTable : $ordinalTable;
        for ($i = 0; $i < 8; ++$i) {
            $y = $sprite->y + $verticalTable[$i];
            $frameBufferOffsetY = $y * 0x100;
            $spriteJ = $sprite->sprite[$i];
            for ($j = 0; $j < 8; ++$j) {
                $x = $sprite->x + $horizontalTable[$j];
                if ($isLowPriority && $this->shouldPixelHide($x, $y)) {
                    continue;
                }
                if ($spriteJ[$j]) {
                    $this->frameBuffer[$x + $frameBufferOffsetY] = $paletteColorsMap[$paletteIndexBase + $spriteJ[$j]];
                }
            }
        }
    }
}
