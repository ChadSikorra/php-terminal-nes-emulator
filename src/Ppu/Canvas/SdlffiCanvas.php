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

use Nes\Ppu\Renderer;
use Serafim\SDL\Event;
use Serafim\SDL\SDL;

final class SdlffiCanvas implements CanvasInterface
{
    private $window;
    private $renderer;
    private $sdl;
    private $event;
    private $color_cache = [];
    private $sdl_points_cache;
    private $sdl_points_value_cache;

    public function __construct()
    {
        $this->sdl = new SDL();
        $this->sdl->SDL_Init(SDL::SDL_INIT_VIDEO);

        $this->window = $this->sdl->SDL_CreateWindow(
            'php-terminal-nes-emulator', SDL::SDL_WINDOWPOS_UNDEFINED, SDL::SDL_WINDOWPOS_UNDEFINED, 256, 224, SDL::SDL_WINDOW_OPENGL
        );
        $this->renderer = $this->sdl->SDL_CreateRenderer($this->window, 0, 0);

        $this->event = $this->sdl->new(Event::class);
        $this->sdl->SDL_RenderClear($this->renderer);
        $this->color_cache = array_fill_keys(Renderer::COLORS, []);
        $this->sdl_points_cache = $this->sdl->new('SDL_Point[' . 224 * 256 .']');
        $this->sdl_points_value_cache = [];
        for ($y = 0; $y < 224; $y++) {
            $y_x_100 = $y * 0x100;
            for ($x = 0; $x < 256; $x++) {
                $index = ($x + $y_x_100);
                $point = $this->sdl->new('SDL_Point');
                $point->x = $x;
                $point->y = $y;
                $this->sdl_points_value_cache[$index] = $point;
            }
        }
    }



    public function draw($frameBuffer, $is_rendered)
    {
        $colorSeparated = $this->color_cache;
        for ($index = 0; $index < 224 * 256; $index++) {
            $colorSeparated[$frameBuffer[$index]][] = $index;
        }
        $sdl_points = $this->sdl_points_cache;
        $sdl_points_value_cache = $this->sdl_points_value_cache;

        foreach ($colorSeparated as $color => $points) {
            if (!$points) {
                continue;
            }
            foreach ($points as $key => $index) {
                $sdl_points[$key] = $sdl_points_value_cache[$index];
            }

            $this->sdl->SDL_SetRenderDrawColor(
                $this->renderer,
                ($color >> 16) & 0xff,
                ($color >> 8) & 0xff,
                $color & 0xff,
                158
            );
            $this->sdl->SDL_RenderDrawPoints($this->renderer, $sdl_points, count($points));
        }
        $this->sdl->SDL_RenderPresent($this->renderer);
    }
}