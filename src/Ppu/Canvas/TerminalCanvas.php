<?php
namespace Nes\Ppu\Canvas;

use Nes\Ppu\Renderer;

class TerminalCanvas implements CanvasInterface
{
    protected $canvas;

    protected $currentSecond = 0;
    protected $framesInSecond = 0;
    protected $fps = 0;
    protected $height = 0;
    protected $lastFrame;
    protected $lastFrameCanvasBuffer;
    /**
     * Braille Pixel Matrix
     *   ,___,
     *   |1 4|
     *   |2 5|
     *   |3 6|
     *   |7 8|
     *   `````
     * @var array
     */
    protected $pixelMap;
    protected $width = 0;

    public $threshold = 127;
    public $frameSkip = 0;
    private $brailleMap = [];
    private $pixelAvgCache0 = [];
    private $pixelAvgCache1 = [];
    private $pixelAvgCache2 = [];
    private $pixelAvgCache3 = [];
    private $pixelAvgCache4 = [];
    private $pixelAvgCache5 = [];
    private $pixelAvgCache6 = [];
    private $pixelAvgCache7 = [];

    public function __construct()
    {
        $this->brailleMap = [];
        for ($i = 0; $i <= 0xff; $i++) {
            $this->brailleMap[$i] = html_entity_decode('&#' . (0x2800 | $i) . ';', ENT_NOQUOTES, 'UTF-8');
        }

        $pixelAverages = [];
        foreach (Renderer::COLORS as $color) {
            $pixelAverages[$color] = (
                    ($color & 0xff) +
                    (($color >> 8) & 0xff) +
                    (($color >> 16))
                ) / 3;
        }

        $pixelMap0 = [
            0x01,
            0x02,
            0x04,
            0x40
        ];
        $pixelMap1 = [
            0x08,
            0x10,
            0x20,
            0x80
        ];
        foreach ($pixelAverages as $color => $avg) {
            $this->pixelAvgCache0[$color] = $avg > $this->threshold ? $pixelMap0[0] : 0;
            $this->pixelAvgCache1[$color] = $avg > $this->threshold ? $pixelMap1[0] : 0;
            $this->pixelAvgCache2[$color] = $avg > $this->threshold ? $pixelMap0[1] : 0;
            $this->pixelAvgCache3[$color] = $avg > $this->threshold ? $pixelMap1[1] : 0;
            $this->pixelAvgCache4[$color] = $avg > $this->threshold ? $pixelMap0[2] : 0;
            $this->pixelAvgCache5[$color] = $avg > $this->threshold ? $pixelMap1[2] : 0;
            $this->pixelAvgCache6[$color] = $avg > $this->threshold ? $pixelMap0[3] : 0;
            $this->pixelAvgCache7[$color] = $avg > $this->threshold ? $pixelMap1[3] : 0;
        }
    }

    public function draw(array $canvasBuffer)
    {
        $pixelAvgCache0 = $this->pixelAvgCache0;
        $pixelAvgCache1 = $this->pixelAvgCache1;
        $pixelAvgCache2 = $this->pixelAvgCache2;
        $pixelAvgCache3 = $this->pixelAvgCache3;
        $pixelAvgCache4 = $this->pixelAvgCache4;
        $pixelAvgCache5 = $this->pixelAvgCache5;
        $pixelAvgCache6 = $this->pixelAvgCache6;
        $pixelAvgCache7 = $this->pixelAvgCache7;

        //Calculate current FPS
        if ($this->currentSecond != time()) {
            $this->fps = $this->framesInSecond;
            $this->currentSecond = time();
            $this->framesInSecond = 1;
        } else {
            ++$this->framesInSecond;
        }

        $screenWidth = 256;
        $screenHeight = 224;
        $charWidth = $screenWidth / 2;
        $charHeight = $screenHeight / 4;

        if ($canvasBuffer != $this->lastFrameCanvasBuffer) {
            $breilleMap = $this->brailleMap;

            $frame = '';
            for ($y = 0, $y_quarter = 0; $y < $screenHeight; $y += 4, ++$y_quarter) {
                $pixelCanvasNumberY0 = ($screenWidth * $y);
                $pixelCanvasNumberY1 = $pixelCanvasNumberY0 + $screenWidth;
                $pixelCanvasNumberY2 = $pixelCanvasNumberY1 + $screenWidth;
                $pixelCanvasNumberY3 = $pixelCanvasNumberY2 + $screenWidth;

                for ($x = 0, $x_half = 0; $x < $screenWidth + 1; $x += 2, ++$x_half) {
                    $pixelCanvasNumber = $x + $pixelCanvasNumberY0;
                    $pixelCanvasNumber2 = $pixelCanvasNumber + 1;
                    $pixelCanvasNumber3 = $x + $pixelCanvasNumberY1;
                    $pixelCanvasNumber4 = $pixelCanvasNumber3 + 1;
                    $pixelCanvasNumber5 = $x + $pixelCanvasNumberY2;
                    $pixelCanvasNumber6 = $pixelCanvasNumber5 + 1;
                    $pixelCanvasNumber7 = $x + $pixelCanvasNumberY3;
                    $pixelCanvasNumber8 = $pixelCanvasNumber7 + 1;

                    $pixel0 = $pixelAvgCache0[$canvasBuffer[$pixelCanvasNumber]];
                    $pixel1 = $pixelAvgCache1[$canvasBuffer[$pixelCanvasNumber2]];
                    $pixel2 = $pixelAvgCache2[$canvasBuffer[$pixelCanvasNumber3]];
                    $pixel3 = $pixelAvgCache3[$canvasBuffer[$pixelCanvasNumber4]];
                    $pixel4 = $pixelAvgCache4[$canvasBuffer[$pixelCanvasNumber5]];
                    $pixel5 = $pixelAvgCache5[$canvasBuffer[$pixelCanvasNumber6]];
                    $pixel6 = $pixelAvgCache6[$canvasBuffer[$pixelCanvasNumber7]];
                    $pixel7 = $pixelAvgCache7[$canvasBuffer[$pixelCanvasNumber8]];

                    $char = $pixel0 | $pixel1 | $pixel2 | $pixel3 | $pixel4 | $pixel5 | $pixel6 | $pixel7;

                    $frame .= $breilleMap[$char];

                    if ($x >= $screenWidth) {
                        $frame .= PHP_EOL;
                    }
                }
            }

            $this->lastFrame = $frame;
            $this->lastFrameCanvasBuffer = $canvasBuffer;

            $content = "\e[H\e[2J";

            if ($this->height > 0 && $this->width > 0) {
                $content = "\e[{$this->height}A\e[{$this->width}D";
            }

            $content .= sprintf('FPS: %3d - Frame Skip: %3d' . PHP_EOL, $this->fps, $this->framesInSecond) . $frame;
            echo $content;

            $this->height = $charHeight + 1;
            $this->width = $charWidth;
        }
    }
}
