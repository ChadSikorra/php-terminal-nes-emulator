<?php

declare(strict_types=1);

namespace Nes\Bus\Mapper;

use Nes\Bus\Exception\UnsupportedMapperException;
use Nes\Bus\Ram;
use Nes\NesFile\NesRom;

class MapperFactory
{
    public function makeMapper(NesRom $nesRom, Ram $ram): MapperInterface
    {
        return match ($nesRom->mapper) {
            0 => new Mapper0($nesRom, $ram),
            default => throw new UnsupportedMapperException(sprintf(
                'The mapper "%s" is not currently supported',
                $nesRom->mapper
            )),
        };
    }
}
