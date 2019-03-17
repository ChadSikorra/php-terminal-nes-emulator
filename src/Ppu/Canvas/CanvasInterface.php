<?php
namespace Nes\Ppu\Canvas;

interface CanvasInterface
{
    public function draw($frameBuffer, $is_rendered);
}
