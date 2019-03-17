<?php

use Nes\Ppu\Canvas\NullCanvas;
use Nes\Ppu\Canvas\PngCanvas;
use Nes\Ppu\Canvas\TerminalCanvas;
use Nes\Ppu\Renderer;
use Nes\ThreadedRenderer;

require_once "vendor/autoload.php";
require "preload.php";

$canvas = new TerminalCanvas();
$renderer = new Renderer($canvas);
$threadedRenderer = new ThreadedRenderer();
error_reporting(0);
