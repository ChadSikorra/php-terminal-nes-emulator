<?php

declare(strict_types=1);

namespace Nes\Ppu\Canvas;

use Nes\Ppu\Renderer\Renderer;
use Symfony\Component\Console\Output\OutputInterface;

class TerminalCanvas implements CanvasInterface
{
    private int $threshold = 127;

    private int $height = 0;

    private string $lastFrame;

    /**
     * @var int[]
     */
    private array $lastFrameCanvasBuffer = [];

    private int $width = 0;

    /**
     * Braille Pixel Matrix.
     *
     *   ,___,
     *   |1 4|
     *   |2 5|
     *   |3 6|
     *   |7 8|
     *   `````
     *
     * @var array<int, string>
     */
    private array $brailleMap;

    /**
     * @var int[]
     */
    private array $pixelAvgCache0 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache1 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache2 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache3 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache4 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache5 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache6 = [];

    /**
     * @var int[]
     */
    private array $pixelAvgCache7 = [];

    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output ?? '';
        $this->brailleMap = [];
        for ($i = 0; $i <= 0xff; ++$i) {
            $this->brailleMap[$i] = html_entity_decode('&#'.(0x2800 | $i).';', ENT_NOQUOTES, 'UTF-8');
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
            0x40,
        ];
        $pixelMap1 = [
            0x08,
            0x10,
            0x20,
            0x80,
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

    /**
     * {@inheritDoc}
     */
    public function draw(array $frameBuffer, int $fps, int $fis): void
    {
        $pixelAvgCache0 = $this->pixelAvgCache0;
        $pixelAvgCache1 = $this->pixelAvgCache1;
        $pixelAvgCache2 = $this->pixelAvgCache2;
        $pixelAvgCache3 = $this->pixelAvgCache3;
        $pixelAvgCache4 = $this->pixelAvgCache4;
        $pixelAvgCache5 = $this->pixelAvgCache5;
        $pixelAvgCache6 = $this->pixelAvgCache6;
        $pixelAvgCache7 = $this->pixelAvgCache7;

        $screenWidth = 256;
        $screenHeight = 224;
        $charWidth = $screenWidth / 2;
        $charHeight = $screenHeight / 4;

        if ($frameBuffer != $this->lastFrameCanvasBuffer) {
            $brailleMap = $this->brailleMap;

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

                    $pixel0 = $pixelAvgCache0[$frameBuffer[$pixelCanvasNumber]];
                    $pixel1 = $pixelAvgCache1[$frameBuffer[$pixelCanvasNumber2]];
                    $pixel2 = $pixelAvgCache2[$frameBuffer[$pixelCanvasNumber3]];
                    $pixel3 = $pixelAvgCache3[$frameBuffer[$pixelCanvasNumber4]];
                    $pixel4 = $pixelAvgCache4[$frameBuffer[$pixelCanvasNumber5]];
                    $pixel5 = $pixelAvgCache5[$frameBuffer[$pixelCanvasNumber6]];
                    $pixel6 = $pixelAvgCache6[$frameBuffer[$pixelCanvasNumber7]];
                    $pixel7 = $pixelAvgCache7[$frameBuffer[$pixelCanvasNumber8]];

                    $char = $pixel0 | $pixel1 | $pixel2 | $pixel3 | $pixel4 | $pixel5 | $pixel6 | $pixel7;

                    $frame .= $brailleMap[$char];

                    if ($x >= $screenWidth) {
                        $frame .= PHP_EOL;
                    }
                }
            }

            $this->lastFrame = $frame;
            $this->lastFrameCanvasBuffer = $frameBuffer;

            $content = "\e[H\e[2J";

            if ($this->height > 0 && $this->width > 0) {
                $content = "\e[{$this->height}A\e[{$this->width}D";
            }

            $content .= sprintf('FPS: %3d - Frame Skip: %3d'.PHP_EOL, $fps, $fis).$frame;
            $this->output->write(
                $content,
                false,
                OutputInterface::OUTPUT_RAW
            );


            $this->height = $charHeight + 1;
            $this->width = $charWidth;
        }
    }
}
