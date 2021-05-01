<?php

declare(strict_types=1);

namespace Nes\Bus\Keypad;

class NullKeypad implements KeypadInterface
{
    public function read(int $player): bool
    {
        return false;
    }

    public function write(int $data): void
    {
    }
}
