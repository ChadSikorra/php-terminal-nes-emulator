<?php

declare(strict_types=1);

namespace Nes\NesFile;

use Exception;
use Nes\Bus\Chr;
use Nes\Bus\Rom;
use Psr\Log\LoggerInterface;

class NesFileParser
{
    public const NES_HEADER_SIZE = 0x0010;

    public const PROGRAM_ROM_SIZE = 0x4000;

    public const CHARACTER_ROM_SIZE = 0x2000;

    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $nesBuffer Rom binary
     *
     * @throws Exception
     */
    public function parse(string $nesBuffer): NesRom
    {
        if ('NES' !== substr($nesBuffer, 0, 3)) {
            throw new Exception('This file is not NES format.');
        }
        $nes = [];
        for ($i = 0; $i < strlen($nesBuffer); ++$i) {
            $nes[$i] = (ord($nesBuffer[$i]) & 0xFF);
        }

        $programRomPages = $nes[4];
        $characterRomPages = $nes[5];
        $isHorizontalMirror = !($nes[6] & 0x01);
        $is4ScreenMirroring = (bool)(($nes[6] >> 3) & 1);
        $isBatteryPresent = (bool)(($nes[6] >> 1) & 1);
        $mapper = ((($nes[6] & 0xF0) >> 4) | $nes[7] & 0xF0);

        $mirrorMode = match (true) {
            $is4ScreenMirroring === true => NesRom::MIRROR_FOUR,
            $isHorizontalMirror ===true => NesRom::MIRROR_HORIZONTAL,
            default => NesRom::MIRROR_VERTICAL
        };

        $characterRomStart = self::NES_HEADER_SIZE + $programRomPages * self::PROGRAM_ROM_SIZE;
        $characterRomEnd = $characterRomStart + $characterRomPages * self::CHARACTER_ROM_SIZE;

        $this->log(sprintf("Rom size: %d (0x%s)", count($nes), dechex(count($nes))));
        $this->log(sprintf("Mirroring mode: %s", $isHorizontalMirror ? 'horizontal' : 'vertical'));
        $this->log(sprintf("4-screen mirror: %s", $is4ScreenMirroring ? 'true' : 'false'));
        $this->log(sprintf("Battery present: %s", $isBatteryPresent ? 'true' : 'false'));
        $this->log(sprintf("Mapper: %d", $mapper));
        $this->log(sprintf("Program ROM pages: %d", $programRomPages));
        $this->log(sprintf("Character ROM pages: %d", $characterRomPages));
        $this->log(sprintf("Character ROM start: 0x%s (%d)", dechex($characterRomStart), $characterRomStart));
        $this->log(sprintf("Character ROM end: 0x%s (%d)", dechex($characterRomEnd), $characterRomEnd));

        $programRom = new Rom(array_slice(
                $nes,
                self::NES_HEADER_SIZE,
                ($characterRomStart - 1) - self::NES_HEADER_SIZE)
        );
        $characterRom = new Chr(array_slice(
            $nes,
            $characterRomStart, ($characterRomEnd - 1) - $characterRomStart)
        );
        $nesRom = new NesRom(
            $mirrorMode,
            $programRom,
            $characterRom,
            $isBatteryPresent,
            $is4ScreenMirroring,
            $mapper
        );

        $this->log(sprintf(
            "Program   ROM: 0x0000 - 0x%s (%d bytes)",
            dechex($nesRom->programRom->size()),
            $nesRom->programRom->size()
        ));
        $this->log(sprintf(
            "Character ROM: 0x0000 - 0x%s (%d bytes)",
            dechex($nesRom->characterRom->size()),
            $nesRom->characterRom->size()
        ));

        return $nesRom;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        } else {
            echo $message . PHP_EOL;
        }
    }
}
