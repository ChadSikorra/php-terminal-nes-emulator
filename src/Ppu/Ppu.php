<?php

declare(strict_types=1);

namespace Nes\Ppu;

use Nes\Bus\PpuBus;
use Nes\Bus\Ram;
use Nes\Cpu\Interrupts;
use function array_fill;

class Ppu implements PpuInterface
{
    private const REG_PPU_CTRL = 0x2000;

    private const REG_PPU_MASK = 0x2001;

    private const REG_PPU_STATUS = 0x2002;

    private const REG_OAM_ADDR = 0x2003;

    private const REG_OAM_DATA = 0x2004;

    private const REG_PPU_SCROLL = 0x2005;

    private const REG_PPU_ADDR = 0x2006;

    private const REG_PPU_DATA = 0x2007;

    private const SPRITES_NUMBER = 0x100;

    private const SPRITE_CONSTANT_MAP = [
        [0x01 << ~~(0 / 8), 0 % 8],
        [0x01 << ~~(1 / 8), 1 % 8],
        [0x01 << ~~(2 / 8), 2 % 8],
        [0x01 << ~~(3 / 8), 3 % 8],
        [0x01 << ~~(4 / 8), 4 % 8],
        [0x01 << ~~(5 / 8), 5 % 8],
        [0x01 << ~~(6 / 8), 6 % 8],
        [0x01 << ~~(7 / 8), 7 % 8],
        [0x01 << ~~(8 / 8), 8 % 8],
        [0x01 << ~~(9 / 8), 9 % 8],
        [0x01 << ~~(10 / 8), 10 % 8],
        [0x01 << ~~(11 / 8), 11 % 8],
        [0x01 << ~~(12 / 8), 12 % 8],
        [0x01 << ~~(13 / 8), 13 % 8],
        [0x01 << ~~(14 / 8), 14 % 8],
        [0x01 << ~~(15 / 8), 15 % 8],
    ];

    // PPU power up state
    // see. https://wiki.nesdev.com/w/index.php/PPU_power_up_state
    //
    // Memory map
    /*
    | addr           |  description               |
    +----------------+----------------------------+
    | 0x0000-0x0FFF  |  Pattern table#0           |
    | 0x1000-0x1FFF  |  Pattern table#1           |
    | 0x2000-0x23BF  |  Name table                |
    | 0x23C0-0x23FF  |  Attribute table           |
    | 0x2400-0x27BF  |  Name table                |
    | 0x27C0-0x27FF  |  Attribute table           |
    | 0x2800-0x2BBF  |  Name table                |
    | 0x2BC0-0x2BFF  |  Attribute table           |
    | 0x2C00-0x2FBF  |  Name Table                |
    | 0x2FC0-0x2FFF  |  Attribute Table           |
    | 0x3000-0x3EFF  |  mirror of 0x2000-0x2EFF   |
    | 0x3F00-0x3F0F  |  background Palette        |
    | 0x3F10-0x3F1F  |  sprite Palette            |
    | 0x3F20-0x3FFF  |  mirror of 0x3F00-0x3F1F   |
    */

    /*
      Control Register1 0x2000

    | bit  | description                                 |
    +------+---------------------------------------------+
    |  7   | Assert NMI when VBlank 0: disable, 1:enable |
    |  6   | PPU master/slave, always 1                  |
    |  5   | Sprite size 0: 8x8, 1: 8x16                 |
    |  4   | Bg pattern table 0:0x0000, 1:0x1000         |
    |  3   | sprite pattern table 0:0x0000, 1:0x1000     |
    |  2   | PPU memory increment 0: +=1, 1:+=32         |
    |  1-0 | Name table 0x00: 0x2000                     |
    |      |            0x01: 0x2400                     |
    |      |            0x02: 0x2800                     |
    |      |            0x03: 0x2C00                     |
    */

    /*
      Control Register2 0x2001

    | bit  | description                                 |
    +------+---------------------------------------------+
    |  7-5 | Background color  0x00: Black               |
    |      |                   0x01: Green               |
    |      |                   0x02: Blue                |
    |      |                   0x04: Red                 |
    |  4   | Enable sprite                               |
    |  3   | Enable background                           |
    |  2   | Sprite mask       render left end           |
    |  1   | Background mask   render left end           |
    |  0   | Display type      0: color, 1: mono         |
    */

    /**
     * @var int[]
     */
    private array $registers;

    /**
     * @var Tile[]
     */
    private array $background = [];

    private int $cycle = 0;

    private int $line = 0;

    private bool $isLowerVramAddr = false;

    private int $spriteRamAddr = 0;

    private int $vramAddr = 0;

    private Ram $vram;

    private int $vramReadBuf = 0;

    private Ram $spriteRam;

    private PpuBus $bus;

    /**
     * @var SpriteWithAttribute[]
     */
    private array $sprites = [];

    private Palette $palette;

    private Interrupts $interrupts;

    private bool $isHorizontalScroll = true;

    private int $scrollX = 0;

    private int $scrollY = 0;

    private bool $isHorizontalMirror;

    /**
     * @var array<int, array>
     */
    private array $defaultSpriteBuffer;

    /**
     * @var array<int, array>
     */
    private array $spriteCache = [[]];

    public function __construct(PpuBus $bus, Interrupts $interrupts, bool $isHorizontalMirror)
    {
        $this->registers = array_fill(self::REG_PPU_CTRL, 7, 0);
        $this->vram = new Ram(0x2000);
        $this->spriteRam = new Ram(0x100);
        $this->bus = $bus;
        $this->interrupts = $interrupts;
        $this->isHorizontalMirror = $isHorizontalMirror;
        $this->palette = new Palette();
        $this->defaultSpriteBuffer = array_fill(0, 8, array_fill(0, 8, 0));
    }


    /**
     * The PPU draws one line at 341 clocks and prepares for the next line.
     * While drawing the BG and sprite at the first 256 clocks,
     * it searches for sprites to be drawn on the next scan line.
     * Get the pattern of the sprite searched with the remaining clock.
     */
    public function run(int $cycle): ?RenderingData
    {
        $this->cycle += $cycle;
        if (0 === $this->line) {
            $this->background = [];
            $this->buildSprites();
        }

        if ($this->cycle >= 341) {
            $this->cycle -= 341;
            ++$this->line;

            if ($this->hasSpriteHit()) {
                $this->registers[self::REG_PPU_STATUS] |= 0x40;
            }

            if ($this->line <= 240 && 0 === $this->line % 8 && $this->scrollY <= 240) {
                $this->buildBackground();
            }

            switch ($this->line) {
                case 241:
                    $this->setVblank();
                    if ($this->registers[self::REG_PPU_CTRL] & 0x80) {
                        $this->interrupts->assertNmi();
                    }
                    break;
                case 262:
                    $this->clearVblank();
                    $this->clearSpriteHit();
                    $this->line = 0;
                    $this->interrupts->deassertNmi();

                    return new RenderingData(
                        $this->palette->read(),
                        $this->isBackgroundEnable() ? $this->background : [],
                        $this->isSpriteEnable() ? $this->sprites : []
                    );
            }
        }

        return null;
    }


    /**
     * | bit  | description                                 |
     * +------+---------------------------------------------+
     * | 7    | 1: VBlank clear by reading this register    |
     * | 6    | 1: sprite hit                               |
     * | 5    | 0: less than 8, 1: 9 or more                |
     * | 4-0  | invalid                                     |
     * |      | bit4 VRAM write flag [0: success, 1: fail]  |
     */
    public function read(int $addr): int
    {
        switch ($addr) {
            case self::REG_PPU_STATUS:
                $this->isHorizontalScroll = true;
                $data = $this->registers[self::REG_PPU_STATUS];
                $this->clearVblank();
                // $this->clearSpriteHit();
                return $data;
            case self::REG_OAM_DATA:
                return $this->spriteRam->read($this->spriteRamAddr);
            case self::REG_PPU_DATA:
                $buf = $this->vramReadBuf;

                if ($this->vramAddr >= 0x2000) {
                    $addr = $this->calcVramAddr();
                    $this->vramAddr += $this->vramOffset();
                    if ($addr >= 0x3F00) {
                        return $this->vram->read($addr);
                    }
                    $this->vramReadBuf = $this->vram->read($addr);
                } else {
                    $this->vramReadBuf = $this->bus->readByPpu($this->vramAddr);
                    $this->vramAddr += $this->vramOffset();
                }

                return $buf;
            default:
                return 0;
        }
    }

    public function write(int $addr, int $data): void
    {
        switch ($addr) {
            case self::REG_OAM_ADDR:
                $this->spriteRamAddr = $data;
                break;
            case self::REG_OAM_DATA:
                $this->spriteRam->write($this->spriteRamAddr, $data);
                ++$this->spriteRamAddr;
                break;
            case self::REG_PPU_SCROLL:
                if ($this->isHorizontalScroll) {
                    $this->isHorizontalScroll = false;
                    $this->scrollX = $data & 0xFF;
                } else {
                    $this->scrollY = $data & 0xFF;
                    $this->isHorizontalScroll = true;
                }
                break;
            case self::REG_PPU_ADDR:
                if ($this->isLowerVramAddr) {
                    $this->vramAddr += $data;
                    $this->isLowerVramAddr = false;
                } else {
                    $this->vramAddr = $data << 8;
                    $this->isLowerVramAddr = true;
                }
                break;
            case self::REG_PPU_DATA:
                if ($this->vramAddr >= 0x2000) {
                    if ($this->vramAddr >= 0x3f00 && $this->vramAddr < 0x4000) {
                        $this->palette->write($this->vramAddr - 0x3f00, $data);
                    } else {
                        $this->vram->write($this->calcVramAddr(), $data);
                    }
                } else {
                    $offset = ($addr >= 0x1000) ? 0x1000 : 0;
                    unset($this->spriteCache[(int) (($addr - $offset) / 16)][$offset]);
                    $this->bus->writeByPpu($addr, $data);
                }
                $this->vramAddr += $this->vramOffset();
                break;
        }
        $this->registers[$addr] = $data;
    }

    public function transferSprite(int $index, int $data): void
    {
        // The DMA transfer will begin at the current OAM write address.
        // It is common practice to initialize it to 0 with a write to PPU 0x2003 before the DMA transfer.
        // Different starting addresses can be used for a simple OAM cycling technique
        // to alleviate sprite priority conflicts by flickering. If using this technique
        // after the DMA OAMADDR should be set to 0 before the end of vblank to prevent potential OAM corruption
        // (See: Errata).
        // However, due to OAMADDR writes also having a "corruption" effect[5] this technique is not recommended.
        $addr = $index + $this->spriteRamAddr;
        $this->spriteRam->write($addr % 0x100, $data);
    }

    private function vramOffset(): int
    {
        return ($this->registers[self::REG_PPU_CTRL] & 0x04) ? 32 : 1;
    }

    private function nameTableId(): int
    {
        return $this->registers[self::REG_PPU_CTRL] & 0x03;
    }

    private function clearSpriteHit(): void
    {
        $this->registers[self::REG_PPU_STATUS] &= 0xbf;
    }

    private function hasSpriteHit(): bool
    {
        $y = $this->spriteRam->read(0);

        return ($y === $this->line)
            && $this->isBackgroundEnable()
            && $this->isSpriteEnable();
    }

    private function isBackgroundEnable(): bool
    {
        return (bool) ($this->registers[self::REG_PPU_MASK] & 0x08);
    }

    private function isSpriteEnable(): bool
    {
        return (bool) ($this->registers[self::REG_PPU_MASK] & 0x10);
    }

    private function scrollTileY(): int
    {
        return ~~(($this->scrollY + (~~($this->nameTableId() / 2) * 240)) / 8);
    }

    private function tileY(): int
    {
        return ~~($this->line / 8) + $this->scrollTileY();
    }

    private function setVblank(): void
    {
        $this->registers[self::REG_PPU_STATUS] |= 0x80;
    }

    private function isVblank(): bool
    {
        return (bool) ($this->registers[self::REG_PPU_STATUS] & 0x80);
    }

    private function clearVblank(): void
    {
        $this->registers[self::REG_PPU_STATUS] &= 0x7F;
    }

    private function getBlockId(int $tileX, int $tileY): int
    {
        return ~~(($tileX % 4) / 2) + (~~(($tileY % 4) / 2)) * 2;
    }

    private function mirrorDownSpriteAddr(int $addr): int
    {
        if (!$this->isHorizontalMirror) {
            return $addr;
        }
        if (($addr >= 0x0400) && ($addr < 0x0800) || ($addr >= 0x0C00)) {
            return $addr - 0x400;
        }

        return $addr;
    }

    /**
     * @param int[] $characterRam
     */
    private function buildTile(int $tileX, int $tileY, int $offset, array $characterRam): Tile
    {
        // INFO see. http://hp.vector.co.jp/authors/VA042397/nes/ppu.html
        $blockId = $this->getBlockId($tileX, $tileY);
        $tileNumber = $tileY * 32 + $tileX;
        $spriteAddr = $this->mirrorDownSpriteAddr($tileNumber + $offset);
        $spriteId = $this->vram->read($spriteAddr);

        $addr = ~~($tileX / 4) + (~~($tileY / 4) * 8) + 0x03C0 + $offset;
        $attr =  $this->vram->read($this->mirrorDownSpriteAddr($addr));

        $paletteId = ($attr >> ($blockId * 2)) & 0x03;
        $sprite = $this->buildSprite(
            $spriteId,
            ($this->registers[self::REG_PPU_CTRL] & 0x10) ? 0x1000 : 0x0000,
            $characterRam
        );

        return new Tile(
            $sprite,
            $paletteId,
            $this->scrollX,
            $this->scrollY
        );
    }

    private function buildBackground(): void
    {
        // INFO: Horizontal offsets range from 0 to 255. "Normal" vertical offsets range from 0 to 239,
        // while values of 240 to 255 are treated as -16 through -1 in a way, but tile data is incorrectly
        // fetched from the attribute table.
        $clampedTileY = $this->tileY() % 30;
        $tableIdOffset = (~~($this->tileY() / 30) % 2) ? 2 : 0;
        $characterRam = $this->bus->characterRam->ram;
        // background of a line.
        // Build viewport + 1 tile for background scroll.
        for ($x = 0; $x < 32 + 1; $x = ($x + 1) | 0) {
            /*
              Name table id and address
              +------------+------------+
              |            |            |
              |  0(0x2000) |  1(0x2400) |
              |            |            |
              +------------+------------+
              |            |            |
              |  2(0x2800) |  3(0x2C00) |
              |            |            |
              +------------+------------+
            */
            $tileX = ($x + ~~(($this->scrollX + (($this->nameTableId() % 2) * 256)) / 8));
            $clampedTileX = $tileX % 32;
            $nameTableId = (~~($tileX / 32) % 2) + $tableIdOffset;
            $offsetAddrByNameTable = $nameTableId * 0x400;
            $tile = $this->buildTile($clampedTileX, $clampedTileY, $offsetAddrByNameTable, $characterRam);
            $this->background[] = $tile;
        }
    }

    private function buildSprites(): void
    {
        $offset = ($this->registers[self::REG_PPU_CTRL] & 0x08) ? 0x1000 : 0x0000;
        $characterRam = $this->bus->characterRam->ram;
        for ($i = 0; $i < self::SPRITES_NUMBER; $i = ($i + 4) | 0) {
            // INFO: Offset sprite Y position, because First and last 8line is not rendered.
            $y = $this->spriteRam->read($i) - 8;
            if ($y < 0) {
                return;
            }
            $spriteId = $this->spriteRam->read($i + 1);
            $attr = $this->spriteRam->read($i + 2);
            $x = $this->spriteRam->read($i + 3);
            $sprite = $this->buildSprite($spriteId, $offset, $characterRam);
            $this->sprites[$i / 4] = new SpriteWithAttribute($sprite, $x, $y, $attr, $spriteId);
        }
    }

    /**
     * @param int[] $characterRam
     *
     * @return array<int[]>
     */
    private function buildSprite(int $spriteId, int $offset, array $characterRam): array
    {
        if (isset($this->spriteCache[$spriteId][$offset])) {
            return $this->spriteCache[$spriteId][$offset];
        }
        $spriteAddressBase = $spriteId * 16 + $offset;

        $sprite = $this->defaultSpriteBuffer;
        for ($i = 0; $i < 16; ++$i) {
            $ram = $characterRam[$spriteAddressBase + $i];
            list($addend, $spriteOffsetBase) = self::SPRITE_CONSTANT_MAP[$i];
            for ($j = 0; $j < 8; ++$j) {
                if ($ram & (0x80 >> $j)) {
                    $sprite[$spriteOffsetBase][$j] += $addend;
                }
            }
        }
        $this->spriteCache[$spriteId][$offset] = $sprite;

        return $sprite;
    }

    private function calcVramAddr(): int
    {
        return ($this->vramAddr >= 0x3000 && $this->vramAddr < 0x3f00)
            ? $this->vramAddr -= 0x3000
            : $this->vramAddr - 0x2000;
    }
}
