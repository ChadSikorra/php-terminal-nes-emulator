<?php

declare(strict_types=1);

namespace Nes\Ppu\Canvas;

use Nes\Ppu\Renderer\Renderer;
use function fopen;
use function fclose;
use function imagecolorallocate;
use function imagesetpixel;
use function imagepng;
use function rewind;
use function stream_get_contents;

class PngReceiverCanvas implements CanvasInterface
{
    /**
     * @var int[]
     */
    private array $colorCache = [];

    /**
     * @var false|resource
     */
    private $image;

    /**
     * @var callable
     */
    private $receiver;

    /**
     * @var array<int, array<int, int>>
     */
    private array $colorRgbMap = [];

    public function __construct(callable $reciever)
    {
        $this->image = imagecreatetruecolor(256, 224);
        $this->receiver = $reciever;
        foreach (Renderer::COLORS as $color) {
            $this->colorRgbMap[$color] = [
                ($color >> 16) & 0xff,
                ($color >> 8) & 0xff,
                $color & 0xff,
            ];
        }
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }

    public function draw(array $frameBuffer, int $fps, int $fis): void
    {
        $memory = fopen('php://memory','r+');

        for ($y = 0; $y < 224; $y++) {
            $y_x_100 = $y * 0x100;
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + $y_x_100);
                if (!isset($this->colorCache[$frameBuffer[$index]])) {
                    $this->colorCache[$frameBuffer[$index]] = imagecolorallocate(
                        $this->image,
                        $this->colorRgbMap[$frameBuffer[$index]][0],
                        $this->colorRgbMap[$frameBuffer[$index]][1],
                        $this->colorRgbMap[$frameBuffer[$index]][2],
                    );
                }
                imagesetpixel($this->image, $x, $y, $this->colorCache[$frameBuffer[$index]]);
            }
        }

        imagepng($this->image, $memory);
        rewind($memory);
        call_user_func($this->receiver, stream_get_contents($memory));
        fclose($memory);
    }
}
