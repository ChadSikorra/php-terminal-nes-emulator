<?php

namespace Nes\Ppu\Canvas;

interface CanvasInterface
{
    /**
     * @param int[] $frameBuffer
     */
    public function draw(array $frameBuffer, int $fps, int $fis): void;
}
