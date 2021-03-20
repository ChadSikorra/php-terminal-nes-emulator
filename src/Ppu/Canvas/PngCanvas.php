<?php

namespace Nes\Ppu\Canvas;

use GdImage;

class PngCanvas implements CanvasInterface
{
    private int $serial = 0;

    /**
     * @var int[]
     */
    private array $colorCache = [];

    /**
     * @var false|GdImage|resource
     */
    private $image;

    public function __construct()
    {
        $this->image = imagecreatetruecolor(256, 224);
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }

    public function draw(array $frameBuffer, int $fps, int $fis): void
    {
        for ($y = 0; $y < 224; ++$y) {
            $y_x_100 = $y * 0x100;
            for ($x = 0; $x < 256; ++$x) {
                $color = $this->getColor($frameBuffer, $x, $y_x_100);
                imagesetpixel($this->image, $x, $y, $color);
            }
        }
        if (!is_dir('screen')) {
            mkdir('screen');
        }
        imagepng($this->image, sprintf('screen/%08d.png', $this->serial++));
    }

    /**
     * @param int[] $frameBuffer
     */
    private function getColor(array $frameBuffer, int $x, int $y_x_100): int
    {
        $index = ($x + $y_x_100);
        if (!isset($this->colorCache[$frameBuffer[$index]])) {
            $blue = $frameBuffer[$index] & 0xff;
            $green = ($frameBuffer[$index] >> 8) & 0xff;
            $red = ($frameBuffer[$index] >> 16) & 0xff;
            $this->colorCache[$frameBuffer[$index]] = imagecolorallocate(
                $this->image,
                $red,
                $green,
                $blue
            );
        }

        return $this->colorCache[$frameBuffer[$index]];
    }
}
