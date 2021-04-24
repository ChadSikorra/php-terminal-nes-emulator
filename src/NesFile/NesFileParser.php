<?php

declare(strict_types=1);

namespace Nes\NesFile;

use Exception;
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
        $this->log(sprintf("Rom size: %d (0x%s)\n", count($nes), dechex(count($nes))));

        $programRomPages = $nes[4];
        $this->log(sprintf("Program ROM pages: %d\n", $programRomPages));
        $characterRomPages = $nes[5];
        $this->log(sprintf("Character ROM pages: %d\n", $characterRomPages));
        $isHorizontalMirror = !($nes[6] & 0x01);
        $mapper = ((($nes[6] & 0xF0) >> 4) | $nes[7] & 0xF0);
        $this->log(sprintf("Mapper: %d\n", $mapper));
        $characterRomStart = self::NES_HEADER_SIZE + $programRomPages * self::PROGRAM_ROM_SIZE;
        $characterRomEnd = $characterRomStart + $characterRomPages * self::CHARACTER_ROM_SIZE;
        $this->log(sprintf("Character ROM start: 0x%s (%d)\n", dechex($characterRomStart), $characterRomStart));
        $this->log(sprintf("Character ROM end: 0x%s (%d)\n", dechex($characterRomEnd), $characterRomEnd));

        $nesRom = new NesRom(
            $isHorizontalMirror,
            array_slice($nes, self::NES_HEADER_SIZE, ($characterRomStart - 1) - self::NES_HEADER_SIZE),
            array_slice($nes, $characterRomStart, ($characterRomEnd - 1) - $characterRomStart)
        );

        $this->log(sprintf(
            "Program   ROM: 0x0000 - 0x%s (%d bytes)\n",
            dechex(count($nesRom->programRom)),
            count($nesRom->programRom)
        ));
        $this->log(sprintf(
            "Character ROM: 0x0000 - 0x%s (%d bytes)\n",
            dechex(count($nesRom->characterRom)),
            count($nesRom->characterRom)
        ));

        return $nesRom;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}
