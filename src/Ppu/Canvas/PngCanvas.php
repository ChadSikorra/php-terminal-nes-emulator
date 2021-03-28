<?php

declare(strict_types=1);

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
        for ($y = 0; $y < 224; $y++) {
            $y_x_100 = $y * 0x100;
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + $y_x_100);
                if (!isset($this->colorCache[$frameBuffer[$index]])) {
                    $this->colorCache[$frameBuffer[$index]] = imagecolorallocate(
                        $this->image,
                        ($frameBuffer[$index] >> 16) & 0xff,
                        ($frameBuffer[$index] >> 8) & 0xff,
                        $frameBuffer[$index] & 0xff
                    );
                }
                imagesetpixel($this->image, $x, $y, $this->colorCache[$frameBuffer[$index]]);
            }
        }
        if (!is_dir('screen')) {
            mkdir('screen');
        }
        imagepng($this->image, sprintf('screen/%08d.png', $this->serial++));
    }
}
