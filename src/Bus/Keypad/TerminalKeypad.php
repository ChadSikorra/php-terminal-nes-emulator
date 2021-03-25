<?php

declare(strict_types=1);

namespace Nes\Bus\Keypad;

use function exec;
use function fopen;
use function fread;
use function array_fill;
use function array_flip;
use function array_values;
use function stream_set_blocking;

class TerminalKeypad implements KeypadInterface
{
    /**
     * @var false|resource
     */
    private $file;

    private string $keyPressing;

    /**
     * @var bool[]
     */
    private array $keyBuffer = [];

    /**
     * @var array<int, bool>
     */
    private array $keyRegisters = [];

    private bool $isSet = false;

    private int $index = 0;

    /**
     * @var array<int, string>
     */
    private array $inputKeys;

    /**
     * @var array<string, int>
     */
    private array $inputIndex;

    public function __construct(array $keypadMap = self::KEYPAD_MAP)
    {
        exec('stty -icanon -echo');
        $this->file = fopen('php://stdin', 'r');
        stream_set_blocking($this->file, false);
        $this->inputKeys = array_values($keypadMap);
        $this->inputIndex = array_flip($this->inputKeys);
        $this->keyBuffer = array_fill(0, 8, false);
    }

    public function fetch(): void
    {
        $key = fread($this->file, 1);

        if (!empty($key)) {
            $this->keyDown($key);
        } elseif (!empty($this->keyPressing)) {
            $this->keyUp($this->keyPressing);
        }

        $this->keyPressing = $key;
    }

    public function write(int $data): void
    {
        if ($data & 0x01) {
            $this->isSet = true;
        } elseif ($this->isSet and !($data & 0x01)) {
            $this->isSet = false;
            $this->index = 0;
            $this->keyRegisters = $this->keyBuffer;
        }
    }

    public function read(): bool
    {
        return (bool)$this->keyRegisters[$this->index++];
    }

    private function keyDown(string $key): void
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = true;
        }
    }

    private function keyUp(string $key): void
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = false;
        }
    }

    private function matchKey(string $key): int
    {
        //Maps a keyboard key to a nes key.
        // A, B, SELECT, START, ↑, ↓, ←, →
        $keyIndex = $this->inputIndex[$key] ?? false;

        if (false === $keyIndex) {
            return -1;
        }

        return $keyIndex;
    }
}
