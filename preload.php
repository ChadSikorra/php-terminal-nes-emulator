<?php
require_once 'src/Bus/CpuBus.php';
require_once 'src/Bus/Keypad.php';
require_once 'src/Bus/PpuBus.php';
require_once 'src/Bus/Ram.php';
require_once 'src/Bus/Rom.php';
require_once 'src/Bus/Rom.php';
require_once 'src/Cpu/Registers/Registers.php';
require_once 'src/Cpu/Registers/Status.php';
require_once 'src/Cpu/Addressing.php';
require_once 'src/Cpu/Cpu.php';
require_once 'src/Cpu/Dma.php';
require_once 'src/Cpu/Interrupts.php';
require_once 'src/Cpu/OpCode.php';
require_once 'src/Cpu/OpCodeProps.php';
require_once 'src/NesFile/NesFile.php';
require_once 'src/NesFile/NesRom.php';
require_once 'src/Ppu/Canvas/NullCanvas.php';
require_once 'src/Ppu/Canvas/PngCanvas.php';
require_once 'src/Ppu/Canvas/TerminalCanvas.php';
require_once 'src/Ppu/Canvas/CanvasInterface.php';
require_once 'src/Ppu/Palette.php';
require_once 'src/Ppu/Ppu.php';
require_once 'src/Ppu/Renderer.php';
require_once 'src/Ppu/RenderingData.php';
require_once 'src/Ppu/SpriteWithAttribute.php';
require_once 'src/Ppu/Tile.php';
require_once 'src/Debugger.php';
require_once 'src/Nes.php';
require_once 'src/ThreadedRenderer.php';

