<?php
namespace Nes\Bus;

class Rom
{
    /** @var int[] */
    public $rom = [];
    /** @var int  */
    private $size = 0;

    public function __construct(array $data)
    {
        $this->rom = $data;
        $this->size = count($data);
    }

    public function size()
    {
        return $this->size;
    }

    public function read(int $addr)
    {
        if (! isset($this->rom[$addr])) {
            throw new \RuntimeException(sprintf(
                "Invalid address on rom read. Address: 0x%s Rom: 0x0000 - 0x%s",
                dechex($addr),
                dechex($this->size)
            ));
        }
        return $this->rom[$addr];
    }
}
