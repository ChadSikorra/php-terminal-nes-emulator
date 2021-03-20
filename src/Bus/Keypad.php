<?php

namespace Nes\Bus;

class Keypad
{
    /**
     * @var bool|resource
     */
    public $file;

    public string $keyPressing;

    /**
     * @var bool[]
     */
    public array $keyBuffer = [];

    /**
     * @var bool[]
     */
    public array $keyRegisters = [];

    public bool $isSet = false;

    public int $index = 0;

    public function __construct()
    {
        exec('stty -icanon -echo');
        $this->file = fopen('php://stdin', 'r');
        stream_set_blocking($this->file, false);

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

    public function keyDown(string $key)
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = true;
        }
    }

    public function keyUp(string $key)
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = false;
        }
    }

    public function matchKey(string $key): int
    {
        //Maps a keyboard key to a nes key.
        // A, B, SELECT, START, ↑, ↓, ←, →
        $keyIndex = array_search($key, ['.', ',', 'n', 'm', 'w', 's', 'a', 'd']);

        if (false === $keyIndex) {
            return -1;
        }

        return $keyIndex;
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
        return $this->keyRegisters[$this->index++];
    }
}
