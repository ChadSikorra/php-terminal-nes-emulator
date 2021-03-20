<?php

namespace Nes\Ppu\Canvas;

interface CanvasInterface
{
    public function draw(array $frameBuffer, int $fps, int $fis): void;
}
