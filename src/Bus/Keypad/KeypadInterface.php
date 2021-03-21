<?php

namespace Nes\Bus\Keypad;

interface KeypadInterface
{
    public const INPUT_START = 'start';

    public const INPUT_SELECT = 'select';

    public const INPUT_A = 'a';

    public const INPUT_B = 'b';

    public const INPUT_UP = 'up';

    public const INPUT_DOWN = 'down';

    public const INPUT_LEFT = 'left';

    public const INPUT_RIGHT = 'right';

    public const KEYPAD_MAP = [
        self::INPUT_A => '.',
        self::INPUT_B => ',',
        self::INPUT_SELECT => 'n',
        self::INPUT_START => 'm',
        self::INPUT_UP => 'w',
        self::INPUT_DOWN => 's',
        self::INPUT_LEFT => 'a',
        self::INPUT_RIGHT => 'd',
    ];

    public function read(): bool;

    public function write(int $data): void;

    public function fetch(): void;
}
