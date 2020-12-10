<?php
namespace Nes\Bus;

class Keypad
{
    /** @var bool|resource */
    public $file;
    /** @var string */
    public $keyPressing;
    /** @var bool[] */
    public $keyBuffer;
    /** @var bool[] */
    public $keyRegistors;
    /** @var bool */
    public $isSet;
    /** @var int */
    public $index;

    public function __construct()
    {
        exec('stty -icanon -echo');
        register_shutdown_function(function() {
            exec('stty echo');
        });
        $this->file = fopen('php://stdin', 'r');
        stream_set_blocking($this->file, false);

        $this->keyBuffer = array_fill(0, 8, false);
    }

    public function fetch()
    {
        $key = fread($this->file, 1);
        if ($key === 'P') {
            die;
        }

        if (! empty($key)) {
            $this->keyDown($key);
        } elseif (! empty($this->keyPressing)) {
            $this->keyUp($this->keyPressing);
        }

        $this->keyPressing = $key;
    }

    public function keyDown($key)
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = true;
        }
    }

    public function keyUp($key)
    {
        $keyIndex = $this->matchKey($key);
        if ($keyIndex > -1) {
            $this->keyBuffer[$keyIndex] = false;
        }
    }

    public function matchKey($key)
    {
        //Maps a keyboard key to a nes key.
        // A, B, SELECT, START, ↑, ↓, ←, →
        $keyIndex = array_search($key, ['.', ',', 'n', 'm', 'w', 's', 'a', 'd']);

        if ($keyIndex === false) {
            return -1;
        }
        return $keyIndex;
    }

    public function write($data)
    {
        if ($data & 0x01) {
            $this->isSet = true;
        } elseif ($this->isSet and !($data & 0x01)) {
            $this->isSet = false;
            $this->index = 0;
            $this->keyRegistors = $this->keyBuffer;
        }
    }

    public function read()
    {
        return $this->keyRegistors[$this->index++];
    }
}
