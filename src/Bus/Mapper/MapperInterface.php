<?php

declare(strict_types=1);

namespace Nes\Bus\Mapper;

interface MapperInterface
{
    public function read(int $addr): int;

    public function write(int $addr, int $data): void;
}
