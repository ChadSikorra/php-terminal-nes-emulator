<?php

/**
 * This file is part of the sj-i/php-profiler package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Nes\Ppu\Canvas;

use Serafim\SDL\Event;
use Serafim\SDL\SDL;

final class SdlffiCanvas implements CanvasInterface
{
    private $window;
    private $renderer;
    private $sdl;
    private $event;

    public function __construct()
    {
        $this->sdl = new SDL();
        $this->sdl->SDL_Init(SDL::SDL_INIT_VIDEO);

        $this->window = $this->sdl->SDL_CreateWindow(
            'php-terminal-nes-emulator', SDL::SDL_WINDOWPOS_UNDEFINED, SDL::SDL_WINDOWPOS_UNDEFINED, 256, 224, SDL::SDL_WINDOW_OPENGL
        );
        $this->renderer = $this->sdl->SDL_CreateRenderer($this->window, 0, SDL::SDL_RENDERER_ACCELERATED|SDL::SDL_RENDERER_PRESENTVSYNC);

        $this->event = $this->sdl->new(Event::class);
        $this->sdl->SDL_RenderClear($this->renderer);
    }



    public function draw($frameBuffer, $is_rendered)
    {
        $colorSeparated = [];
        for ($y = 0; $y < 224; $y++) {
            $y_x_100 = $y * 0x100;
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + $y_x_100);
                $color = $frameBuffer[$index];
                $colorSeparated[$color] ??= [];
                $colorSeparated[$color][] = [$x, $y];
            }
        }

        foreach ($colorSeparated as $color => $points) {
            $blue = $color & 0xff;
            $green = ($color >> 8) & 0xff;
            $red = ($color >> 16) & 0xff;

            $sdl_points = $this->sdl->new('SDL_Point[' . count($points) . ']');
            foreach ($points as $key => [$x, $y]) {
                $sdl_points[$key]->x = $x;
                $sdl_points[$key]->y = $y;
            }
            $this->sdl->SDL_SetRenderDrawColor($this->renderer, $red, $green, $blue, 158);
            $this->sdl->SDL_RenderDrawPoints($this->renderer, $sdl_points, count($points));
        }
        $this->sdl->SDL_RenderPresent($this->renderer);
    }
}