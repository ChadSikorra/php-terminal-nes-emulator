<?php

declare(strict_types=1);

namespace Nes;

class Debugger
{
    /**
     * @param int[] $array
     */
    public static function dump(array $array): void
    {
        foreach ($array as $idx => $byte) {
            if (0 == $idx % 16) {
                printf("\n%04x ", $idx);
            }
            if (0 == $idx % 8) {
                printf(' ');
            }
            printf('%02x ', $byte);
        }
    }
}
