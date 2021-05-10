<?php

declare(strict_types=1);

namespace Nes\Ppu\Renderer;

use Nes\Ppu\Canvas\CanvasInterface;
use Nes\Ppu\RenderingData;
use Nes\Ppu\SpriteWithAttribute;
use Nes\Ppu\Tile;

class Renderer implements RendererInterface
{
    /**
     * @var int[]
     */
    private array $frameBuffer = [];

    /**
     * @var Tile[]
     */
    private array $background;

    private CanvasInterface $canvas;

    private int $currentSecond = 0;

    private int $framesInSecond = 0;

    private int $fps = 0;

    public function __construct(CanvasInterface $canvas)
    {
        // 256 x 240
        $this->frameBuffer = array_fill(0, 256 * 240, 0);

        $this->canvas = $canvas;
    }

    public function render(RenderingData $data): void
    {
        //Calculate current FPS
        if ($this->currentSecond != time()) {
            $this->fps = $this->framesInSecond;
            $this->currentSecond = time();
            $this->framesInSecond = 1;
        } else {
            ++$this->framesInSecond;
        }

        if ($data->background || $data->sprites) {
            $paletteColorsMap = [];
            $colors = self::COLORS;

            foreach ($data->palette as $key => $colorIndex) {
                $paletteColorsMap[$key] = $colors[$colorIndex];
            }

            if ($data->background) {
                $this->renderBackground($data->background, $paletteColorsMap);
            }
            if ($data->sprites) {
                $this->renderSprites($data->sprites, $paletteColorsMap);
            }
        }

        $this->canvas->draw(
            $this->frameBuffer,
            $this->fps,
            $this->framesInSecond
        );
    }

    /**
     * @param int $x
     * @param int $y
     * @return bool
     */
    private function shouldPixelHide($x, $y): bool
    {
        $tileX = (int) ($x / 8);
        $tileY = (int) ($y / 8);
        $backgroundIndex = $tileY * 33 + $tileX;

        $sprite = null;
        if (isset($this->background[$backgroundIndex]) && $this->background[$backgroundIndex]->pattern) {
            $sprite = $this->background[$backgroundIndex]->pattern;
        }
        if (!$sprite) {
            return true;
        }

        // NOTE: If background pixel is not transparent, we need to hide sprite.
        return !(($sprite[$y % 8] && $sprite[$y % 8][$x % 8] % 4) === false);
    }

    /**
     * @param Tile[] $background
     * @param int[]  $paletteColorsMap
     */
    private function renderBackground(array $background, array $paletteColorsMap): void
    {
        $count_background = count($background);
        $this->background = $background;
        for ($i = 0; $i < $count_background; ++$i) {
            $x = ($i % 33) * 8;
            $y = (int) ($i / 33) * 8;
            $this->renderTile($background[$i], $x, $y, $paletteColorsMap);
        }
    }

    /**
     * @param SpriteWithAttribute[] $sprites
     * @param int[]                 $paletteColorsMap
     */
    private function renderSprites(array $sprites, array $paletteColorsMap): void
    {
        foreach ($sprites as $sprite) {
            $this->renderSprite($sprite, $paletteColorsMap);
        }
    }

    /**
     * @param Tile $tile
     * @param int $tileX
     * @param int $tileY
     * @param int[] $paletteColorsMap
     */
    private function renderTile($tile, $tileX, $tileY, $paletteColorsMap): void
    {
        //{ sprite, paletteId, scrollX, scrollY }: Tile
        $offsetX = $tileX - ($tile->scrollX % 8);
        $offsetY = $tileY - ($tile->scrollY % 8);
        $paletteIndexBase = $tile->paletteId * 4;
        $colorMap = [];
        foreach ($paletteColorsMap as $key => $value) {
            $colorMap[$key - $paletteIndexBase] = $value;
        }
        if ($offsetX >= 0 && 0xFF >= ($offsetX + 7)) {
            for ($i = 0; $i < 8; ++$i) {
                $y = $i + $offsetY;
                if ($y >= 0 && $y < 224) {
                    $frameBufferOffsetY = $y * 0x100;
                    $pattern = $tile->pattern[$i];
                    $frameBufferOffset = $offsetX + $frameBufferOffsetY;
                    $this->frameBuffer[$frameBufferOffset] = $colorMap[$pattern[0]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[1]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[2]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[3]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[4]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[5]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[6]];
                    $this->frameBuffer[++$frameBufferOffset] = $colorMap[$pattern[7]];
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
                            $this->frameBuffer[$x + $frameBufferOffsetY] = $paletteColorsMap[$paletteIndexBase + $pattern[$j]];
                        }
                    }
                }
            }
        }
    }

    /**
     * @param SpriteWithAttribute $sprite
     * @param int[] $paletteColorsMap
     */
    private function renderSprite($sprite, $paletteColorsMap): void
    {
        $isVerticalReverse = (bool) ($sprite->attribute & 0x80);
        $isHorizontalReverse = (bool) ($sprite->attribute & 0x40);
        $isLowPriority = (bool) ($sprite->attribute & 0x20);
        $paletteId = $sprite->attribute & 0x03;
        $paletteIndexBase = $paletteId * 4 + 0x10;
        for ($i = 0; $i < 8; $i = ($i + 1) | 0) {
            $y = $sprite->y + ($isVerticalReverse ? 7 - $i : $i);
            $frameBufferOffsetY = $y * 0x100;
            for ($j = 0; $j < 8; $j = ($j + 1) | 0) {
                $x = $sprite->x + ($isHorizontalReverse ? 7 - $j : $j);
                if ($isLowPriority && $this->shouldPixelHide($x, $y)) {
                    continue;
                }
                if ($sprite->sprite[$i][$j]) {
                    $this->frameBuffer[$x + $frameBufferOffsetY] = $paletteColorsMap[$paletteIndexBase + $sprite->sprite[$i][$j]];
                }
            }
        }
    }
}
