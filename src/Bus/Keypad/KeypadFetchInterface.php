<?php

declare(strict_types=1);

namespace Nes\Bus\Keypad;

interface KeypadFetchInterface
{
    /**
     * Called once per render if implemented by the Keypad.
     */
    public function fetch(): void;
}
