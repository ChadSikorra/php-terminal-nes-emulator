<?php
namespace Nes\Cpu;

use Nes\Bus\CpuBus;
use Nes\Cpu\Registers\Registers;
use Nes\Debugger;

class Cpu
{
    /** @var \Nes\Cpu\Registers\Registers */
    public $registers;
    /** @var bool */
    public $hasBranched;
    /** @var \Nes\Bus\CpuBus */
    public $bus;
    /** @var \Nes\Cpu\OpCodeProps[] */
    public $opCodeList;
    /** @var \Nes\Cpu\Interrupts */
    public $interrupts;

    public $rom;
    private $romCache;
    private $wordRomCache;
    public $ram;

    const CPU_CLOCK = 1789772.5;

    public function __construct(CpuBus $bus, Interrupts $interrupts)
    {
        $this->bus = $bus;
        $this->rom = $bus->programRom;
        $this->romCache = $bus->programRom->rom;
        $wordRomCache = [];
        $romSize = count($this->romCache);
        for ($i = 0; $i < $romSize; $i++) {
            $wordRomCache[$i] = $this->romCache[$i] | ($this->romCache[$i + 1] << 8);
        }
        $this->wordRomCache = $wordRomCache;
        $this->ram = $bus->ram;
        $this->interrupts = $interrupts;

        $this->registers = Registers::getDefault();
        $this->hasBranched = false;
        $this->opCodeList = [];

        $opCodes = OpCode::getOpCodes();
        foreach ($opCodes as $key => $op) {
            $this->opCodeList[hexdec($key)] = $op;
        }
    }


    public function reset()
    {
        $this->registers = Registers::getDefault();
        // TODO: flownes set 0x8000 to PC when read(0xfffc) fails.
        $this->registers->pc = $this->readWord(0xFFFC);
        printf("Initial pc: %04x\n", $this->registers->pc);
    }

    private function fetchByte($addr)
    {
        ++$this->registers->pc;
//        $addr &= 0xFFFF;

        if ($addr >= 0xC000) {
            // Mirror, if prom block number equals 1
            if ($this->bus->use_mirror) {
                return $this->romCache[$addr - 0xC000];
            }
            return $this->romCache[$addr - 0x8000];
        } elseif ($addr >= 0x8000) {
            // ROM
            return $this->romCache[$addr - 0x8000];
        } elseif ($addr < 0x0800) {
            return $this->ram->read($addr);
        } elseif ($addr < 0x2000) {
            // mirror
            return $this->ram->read($addr - 0x0800);
        } elseif ($addr < 0x4000) {
            // mirror
            $data = $this->bus->ppu->read(($addr - 0x2000) % 8);
            return $data;
        } elseif ($addr === 0x4016) {
            // TODO Add 2P
            return $this->bus->keypad->read();
        }
        return false;
    }

    private function fetchWord($addr)
    {
        $this->registers->pc += 2;
//        $addr &= 0xFFFF;

        if ($addr >= 0xC000) {
            // Mirror, if prom block number equals 1
            if ($this->bus->use_mirror) {
                $addr -= 0xC000;
                return $this->wordRomCache[$addr];
            }
            $addr -= 0x8000;
            return $this->wordRomCache[$addr];
        } elseif ($addr >= 0x8000) {
            $addr -= 0x8000;
            // ROM
            return $this->wordRomCache[$addr];
        } elseif ($addr < 0x0800) {
            $ram = $this->ram->ram;
            return $ram[$addr] | ($ram[$addr + 1] << 8);
        } elseif ($addr < 0x2000) {
            // mirror
            $ram = $this->ram->ram;
            $addr -= 0x8000;
            return $ram[$addr] | ($ram[$addr + 1] << 8);
        } elseif ($addr < 0x4000) {
            // mirror
            $ppu = $this->bus->ppu;
            $addr -= 0x2000;
            $data1 = $ppu->read(($addr) % 8);
            $data2 = $ppu->read(($addr + 1) % 8) << 8;
            return $data1 | $data2;
        } elseif ($addr === 0x4016) {
            // TODO Add 2P
            $keypad = $this->bus->keypad;
            return $keypad->read() | ($keypad->read() << 8);
        }
        return false;
    }

    private function readByte($addr)
    {
        $addr &= 0xFFFF;

        return $this->bus->readByCpu($addr);
    }

    private function readWord($addr)
    {
//        $addr &= 0xFFFF;

        if ($addr >= 0xC000) {
            // Mirror, if prom block number equals 1
            if ($this->bus->use_mirror) {
                $addr -= 0xC000;
                return $this->wordRomCache[$addr];
            }
            $addr -= 0x8000;
            return $this->wordRomCache[$addr];
        } elseif ($addr >= 0x8000) {
            $addr -= 0x8000;
            // ROM
            return $this->wordRomCache[$addr];
        } elseif ($addr < 0x0800) {
            $ram = $this->ram->ram;
            return $ram[$addr] | ($ram[$addr + 1] << 8);
        } elseif ($addr < 0x2000) {
            // mirror
            $ram = $this->ram->ram;
            $addr -= 0x8000;
            return $ram[$addr] | ($ram[$addr + 1] << 8);
        } elseif ($addr < 0x4000) {
            // mirror
            $ppu = $this->bus->ppu;
            $addr -= 0x2000;
            $data1 = $ppu->read(($addr) % 8);
            $data2 = $ppu->read(($addr + 1) % 8) << 8;
            return $data1 | $data2;
        } elseif ($addr === 0x4016) {
            // TODO Add 2P
            $keypad = $this->bus->keypad;
            return $keypad->read() | ($keypad->read() << 8);
        }
        return false;
    }

    public function write(int $addr, int $data)
    {
        $this->bus->writeByCpu($addr, $data);
    }

    public function push(int $data)
    {
        $this->write(0x100 | ($this->registers->sp & 0xFF), $data);
        $this->registers->sp--;
    }

    public function pop(): int
    {
        $this->registers->sp++;

        return $this->readByte(0x100 | ($this->registers->sp & 0xFF));
    }

    public function branch($addr)
    {
        $this->registers->pc = $addr;
        $this->hasBranched = true;
    }

    public function pushStatus()
    {
        $status = (+$this->registers->p->negative) << 7 |
            (+$this->registers->p->overflow) << 6 |
            (+$this->registers->p->reserved) << 5 |
            (+$this->registers->p->break_mode) << 4 |
            (+$this->registers->p->decimal_mode) << 3 |
            (+$this->registers->p->interrupt) << 2 |
            (+$this->registers->p->zero) << 1 |
            (+$this->registers->p->carry);
        $this->push($status);
    }

    public function popStatus()
    {
        $status = $this->pop();
        $this->registers->p->negative = (bool)($status & 0x80);
        $this->registers->p->overflow = (bool)($status & 0x40);
        $this->registers->p->reserved = (bool)($status & 0x20);
        $this->registers->p->break_mode = (bool)($status & 0x10);
        $this->registers->p->decimal_mode = (bool)($status & 0x08);
        $this->registers->p->interrupt = (bool)($status & 0x04);
        $this->registers->p->zero = (bool)($status & 0x02);
        $this->registers->p->carry = (bool)($status & 0x01);
    }

    public function popPC()
    {
        $this->registers->pc = $this->pop();
        $this->registers->pc += ($this->pop() << 8);
    }

    public function processNmi()
    {
        $this->interrupts->deassertNmi();
        $this->registers->p->break_mode = false;
        $this->push(($this->registers->pc >> 8) & 0xFF);
        $this->push($this->registers->pc & 0xFF);
        $this->pushStatus();
        $this->registers->p->interrupt = true;
        $this->registers->pc = $this->readWord(0xFFFA);
    }

    public function processIrq()
    {
        if ($this->registers->p->interrupt) {
            return;
        }
        $this->interrupts->deassertIrq();
        $this->registers->p->break_mode = false;
        $this->push(($this->registers->pc >> 8) & 0xFF);
        $this->push($this->registers->pc & 0xFF);
        $this->pushStatus();
        $this->registers->p->interrupt = true;
        $this->registers->pc = $this->readWord(0xFFFE);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function run()
    {
        $cycle = 0;
        $registers = $this->registers;
        $interrupts = $this->interrupts;
        $p = $registers->p;
        while ($cycle < 32) {

            if ($interrupts->nmi) {
                $this->processNmi();
            }
            if ($interrupts->irq) {
                $this->processIrq();
            }
            $pc = $registers->pc;
            $opcode = $this->fetchByte($pc++);
            $ocp = $this->opCodeList[$opcode];
            switch ($ocp->mode) {
                case Addressing::Absolute:
                    $addrOrData = ($this->fetchWord($pc));
                    $additionalCycle = 0;
                    break;
                case Addressing::Accumulator:
                    $addrOrData = 0x00;
                    $additionalCycle = 0;
                    break;
                case Addressing::Implied:
                    $addrOrData = 0x00;
                    $additionalCycle = 0;
                    break;
                case Addressing::Immediate:
                    $addrOrData = $this->fetchByte($pc);
                    $additionalCycle = 0;
                    break;
                case Addressing::Relative:
                    $baseAddr = $this->fetchByte($pc);
                    $pc += 1;
                    $addr = $baseAddr < 0x80 ? $baseAddr + $pc : $baseAddr + $pc - 256;
                    $addrOrData = $addr;
                    $additionalCycle = ($addr & 0xff00) !== ($pc & 0xFF00) ? 1 : 0;
                    break;
                case Addressing::ZeroPage:
                    $addrOrData = $this->fetchByte($pc);
                    $additionalCycle = 0;
                    break;
                case Addressing::ZeroPageX:
                    $addr = $this->fetchByte($pc);
                    $addrOrData = ($addr + $registers->x) & 0xff;
                    $additionalCycle = 0;
                    break;
                case Addressing::ZeroPageY:
                    $addr = $this->fetchByte($pc);
                    $addrOrData = ($addr + $registers->y & 0xff);
                    $additionalCycle = 0;
                    break;
                case Addressing::AbsoluteX:
                    $addr = ($this->fetchWord($pc));
                    $additionalCycle = ($addr & 0xFF00) !== (($addr + $registers->x) & 0xFF00) ? 1 : 0;
                    $addrOrData = ($addr + $registers->x) & 0xFFFF;
                    break;
                case Addressing::AbsoluteY:
                    $addr = ($this->fetchWord($pc));
                    $additionalCycle = ($addr & 0xFF00) !== (($addr + $registers->y) & 0xFF00) ? 1 : 0;
                    $addrOrData = ($addr + $registers->y) & 0xFFFF;
                    break;
                case Addressing::PreIndexedIndirect:
                    $baseAddr = ($this->fetchByte($pc) + $registers->x) & 0xFF;
                    $addr = $this->readByte($baseAddr) + ($this->readByte(($baseAddr + 1) & 0xFF) << 8);
                    $addrOrData = $addr & 0xFFFF;
                    $additionalCycle = ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0;
                    break;
                case Addressing::PostIndexedIndirect:
                    $addrOrData = $this->fetchByte($pc);
                    $baseAddr = $this->readByte($addrOrData) + ($this->readByte(($addrOrData + 1) & 0xFF) << 8);
                    $addr = $baseAddr + $registers->y;
                    $addrOrData = $addr & 0xFFFF;
                    $additionalCycle = ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0;
                    break;
                case Addressing::IndirectAbsolute:
                    $addrOrData = $this->fetchWord($pc);
                    $addr = $this->readByte($addrOrData) +
                        ($this->readByte(($addrOrData & 0xFF00) | ((($addrOrData & 0xFF) + 1) & 0xFF)) << 8);
                    $addrOrData = $addr & 0xFFFF;
                    $additionalCycle = 0;
                    break;
                default:
                    echo($ocp->mode);
                    throw new \Exception(`Unknown addressing {$ocp->mode} detected.`);
            }
//        list($addrOrData, $additionalCycle) = $this->getAddrOrDataWithAdditionalCycle($ocp->mode);
//        $this->execInstruction($ocp, $addrOrData);
            $this->hasBranched = false;
            switch ($ocp->baseType) {
                case Opcode::BASE_LDA:
                    $registers->a = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !$registers->a;
                    break;
                case Opcode::BASE_LDX:
                    $registers->x = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $p->negative = (bool)($registers->x & 0x80);
                    $p->zero = !$registers->x;
                    break;
                case Opcode::BASE_LDY:
                    $registers->y = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $p->negative = (bool)($registers->y & 0x80);
                    $p->zero = !$registers->y;
                    break;
                case Opcode::BASE_STA:
                    $this->write($addrOrData, $registers->a);
                    break;
                case Opcode::BASE_STX:
                    $this->write($addrOrData, $registers->x);
                    break;
                case Opcode::BASE_STY:
                    $this->write($addrOrData, $registers->y);
                    break;
                case Opcode::BASE_TAX:
                    $registers->x = $registers->a;
                    $p->negative = (bool)($registers->x & 0x80);
                    $p->zero = !$registers->x;
                    break;
                case Opcode::BASE_TAY:
                    $registers->y = $registers->a;
                    $p->negative = (bool)($registers->y & 0x80);
                    $p->zero = !$registers->y;
                    break;
                case Opcode::BASE_TSX:
                    $registers->x = $registers->sp & 0xFF;
                    $p->negative = (bool)($registers->x & 0x80);
                    $p->zero = !$registers->x;
                    break;
                case Opcode::BASE_TXA:
                    $registers->a = $registers->x;
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !$registers->a;
                    break;
                case Opcode::BASE_TXS:
                    $registers->sp = $registers->x + 0x0100;
                    break;
                case Opcode::BASE_TYA:
                    $registers->a = $registers->y;
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !$registers->a;
                    break;
                case Opcode::BASE_ADC:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $operated = $data + $registers->a + $p->carry;
                    $overflow = (!((($registers->a ^ $data) & 0x80) != 0) &&
                        ((($registers->a ^ $operated) & 0x80)) != 0);
                    $p->overflow = $overflow;
                    $p->carry = $operated > 0xFF;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !($operated & 0xFF);
                    $registers->a = $operated & 0xFF;
                    break;
                case Opcode::BASE_AND:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $operated = $data & $registers->a;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !$operated;
                    $registers->a = $operated & 0xFF;
                    break;
                case Opcode::BASE_ASL:
                    if ($ocp->mode == Addressing::Accumulator) {
                        $acc = $registers->a;
                        $p->carry = (bool)($acc & 0x80);
                        $registers->a = ($acc << 1) & 0xFF;
                        $p->zero = !$registers->a;
                        $p->negative = (bool)($registers->a & 0x80);
                    } else {
                        $data = $this->readByte($addrOrData);
                        $p->carry = (bool)($data & 0x80);
                        $shifted = ($data << 1) & 0xFF;
                        $this->write($addrOrData, $shifted);
                        $p->zero = !$shifted;
                        $p->negative = (bool)($shifted & 0x80);
                    }
                    break;
                case Opcode::BASE_BIT:
                    $data = $this->readByte($addrOrData);
                    $p->negative = (bool)($data & 0x80);
                    $p->overflow = (bool)($data & 0x40);
                    $p->zero = !($registers->a & $data);
                    break;
                case Opcode::BASE_CMP:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $compared = $registers->a - $data;
                    $p->carry = $compared >= 0;
                    $p->negative = (bool)($compared & 0x80);
                    $p->zero = !($compared & 0xff);
                    break;
                case Opcode::BASE_CPX:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $compared = $registers->x - $data;
                    $p->carry = $compared >= 0;
                    $p->negative = (bool)($compared & 0x80);
                    $p->zero = !($compared & 0xff);
                    break;
                case Opcode::BASE_CPY:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $compared = $registers->y - $data;
                    $p->carry = $compared >= 0;
                    $p->negative = (bool)($compared & 0x80);
                    $p->zero = !($compared & 0xff);
                    break;
                case Opcode::BASE_DEC:
                    $data = ($this->readByte($addrOrData) - 1) & 0xFF;
                    $p->negative = (bool)($data & 0x80);
                    $p->zero = !$data;
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_DEX:
                    $registers->x = ($registers->x - 1) & 0xFF;
                    $p->negative = (bool)($registers->x & 0x80);
                    $p->zero = !$registers->x;
                    break;
                case Opcode::BASE_DEY:
                    $registers->y = ($registers->y - 1) & 0xFF;
                    $p->negative = (bool)($registers->y & 0x80);
                    $p->zero = !$registers->y;
                    break;
                case Opcode::BASE_EOR:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $operated = $data ^ $registers->a;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !$operated;
                    $registers->a = $operated & 0xFF;
                    break;
                case Opcode::BASE_INC:
                    $data = ($this->readByte($addrOrData) + 1) & 0xFF;
                    $p->negative = (bool)($data & 0x80);
                    $p->zero = !$data;
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_INX:
                    $registers->x = ($registers->x + 1) & 0xFF;
                    $p->negative = (bool)($registers->x & 0x80);
                    $p->zero = !$registers->x;
                    break;
                case Opcode::BASE_INY:
                    $registers->y = ($registers->y + 1) & 0xFF;
                    $p->negative = (bool)($registers->y & 0x80);
                    $p->zero = !$registers->y;
                    break;
                case Opcode::BASE_LSR:
                    if ($ocp->mode == Addressing::Accumulator) {
                        $acc = $registers->a & 0xFF;
                        $p->carry = (bool)($acc & 0x01);
                        $registers->a = $acc >> 1;
                        $p->zero = !$registers->a;
                    } else {
                        $data = $this->readByte($addrOrData);
                        $p->carry = (bool)($data & 0x01);
                        $p->zero = !($data >> 1);
                        $this->write($addrOrData, $data >> 1);
                    }
                    $p->negative = false;
                    break;
                case Opcode::BASE_ORA:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $operated = $data | $registers->a;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !$operated;
                    $registers->a = $operated & 0xFF;
                    break;
                case Opcode::BASE_ROL:
                    if ($ocp->mode == Addressing::Accumulator) {
                        $acc = $registers->a;
                        $registers->a = ($acc << 1) & 0xFF | ($p->carry ? 0x01 : 0x00);
                        $p->carry = (bool)($acc & 0x80);
                        $p->zero = !$registers->a;
                        $p->negative = (bool)($registers->a & 0x80);
                    } else {
                        $data = $this->readByte($addrOrData);
                        $writeData = ($data << 1 | ($p->carry ? 0x01 : 0x00)) & 0xFF;
                        $this->write($addrOrData, $writeData);
                        $p->carry = (bool)($data & 0x80);
                        $p->zero = !$writeData;
                        $p->negative = (bool)($writeData & 0x80);
                    }
                    break;
                case Opcode::BASE_ROR:
                    if ($ocp->mode == Addressing::Accumulator) {
                        $acc = $registers->a;
                        $registers->a = $acc >> 1 | ($p->carry ? 0x80 : 0x00);
                        $p->carry = (bool)($acc & 0x01);
                        $p->zero = !$registers->a;
                        $p->negative = (bool)($registers->a & 0x80);
                    } else {
                        $data = $this->readByte($addrOrData);
                        $writeData = $data >> 1 | ($p->carry ? 0x80 : 0x00);
                        $this->write($addrOrData, $writeData);
                        $p->carry = (bool)($data & 0x01);
                        $p->zero = !$writeData;
                        $p->negative = (bool)($writeData & 0x80);
                    }
                    break;
                case Opcode::BASE_SBC:
                    $data = ($ocp->mode == Addressing::Immediate) ? $addrOrData : $this->readByte($addrOrData);
                    $operated = $registers->a - $data - ($p->carry ? 0 : 1);
                    $overflow = ((($registers->a ^ $operated) & 0x80) != 0 &&
                        (($registers->a ^ $data) & 0x80) != 0);
                    $p->overflow = $overflow;
                    $p->carry = $operated >= 0;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !($operated & 0xFF);
                    $registers->a = $operated & 0xFF;
                    break;
                case Opcode::BASE_PHA:
                    $this->push($registers->a);
                    break;
                case Opcode::BASE_PHP:
                    $p->break_mode = true;
                    $this->pushStatus();
                    break;
                case Opcode::BASE_PLA:
                    $registers->a = $this->pop();
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !$registers->a;
                    break;
                case Opcode::BASE_PLP:
                    $this->popStatus();
                    $p->reserved = true;
                    break;
                case Opcode::BASE_JMP:
                    $registers->pc = $addrOrData;
                    break;
                case Opcode::BASE_JSR:
                    $pc = $registers->pc - 1;
                    $this->push(($pc >> 8) & 0xFF);
                    $this->push($pc & 0xFF);
                    $registers->pc = $addrOrData;
                    break;
                case Opcode::BASE_RTS:
                    $this->popPC();
                    $registers->pc++;
                    break;
                case Opcode::BASE_RTI:
                    $this->popStatus();
                    $this->popPC();
                    $p->reserved = true;
                    break;
                case Opcode::BASE_BCC:
                    if (!$p->carry) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BCS:
                    if ($p->carry) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BEQ:
                    if ($p->zero) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BMI:
                    if ($p->negative) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BNE:
                    if (!$p->zero) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BPL:
                    if (!$p->negative) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BVS:
                    if ($p->overflow) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_BVC:
                    if (!$p->overflow) {
                        $this->branch($addrOrData);
                    }
                    break;
                case Opcode::BASE_CLD:
                    $p->decimal_mode = false;
                    break;
                case Opcode::BASE_CLC:
                    $p->carry = false;
                    break;
                case Opcode::BASE_CLI:
                    $p->interrupt = false;
                    break;
                case Opcode::BASE_CLV:
                    $p->overflow = false;
                    break;
                case Opcode::BASE_SEC:
                    $p->carry = true;
                    break;
                case Opcode::BASE_SEI:
                    $p->interrupt = true;
                    break;
                case Opcode::BASE_SED:
                    $p->decimal_mode = true;
                    break;
                case Opcode::BASE_BRK:
                    $interrupt = $p->interrupt;
                    $registers->pc++;
                    $this->push(($registers->pc >> 8) & 0xFF);
                    $this->push($registers->pc & 0xFF);
                    $p->break_mode = true;
                    $this->pushStatus();
                    $p->interrupt = true;
                    // Ignore interrupt when already set.
                    if (!$interrupt) {
                        $registers->pc = $this->readWord(0xFFFE);
                    }
                    $registers->pc--;
                    break;
                case Opcode::BASE_NOP:
                    break;
                // Unofficial Opecode
                case Opcode::BASE_NOPD:
                    $registers->pc++;
                    break;
                case Opcode::BASE_NOPI:
                    $registers->pc += 2;
                    break;
                case Opcode::BASE_LAX:
                    $registers->a = $registers->x = $this->readByte($addrOrData);
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !$registers->a;
                    break;
                case Opcode::BASE_SAX:
                    $operated = $registers->a & $registers->x;
                    $this->write($addrOrData, $operated);
                    break;
                case Opcode::BASE_DCP:
                    $operated = ($this->readByte($addrOrData) - 1) & 0xFF;
                    $p->negative = (bool)((($registers->a - $operated) & 0x1FF) & 0x80);
                    $p->zero = !(($registers->a - $operated) & 0x1FF);
                    $this->write($addrOrData, $operated);
                    break;
                case Opcode::BASE_ISB:
                    $data = ($this->readByte($addrOrData) + 1) & 0xFF;
                    $operated = (~$data & 0xFF) + $registers->a + $p->carry;
                    $overflow = (!((($registers->a ^ $data) & 0x80) != 0) &&
                        ((($registers->a ^ $operated) & 0x80)) != 0);
                    $p->overflow = $overflow;
                    $p->carry = $operated > 0xFF;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !($operated & 0xFF);
                    $registers->a = $operated & 0xFF;
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_SLO:
                    $data = $this->readByte($addrOrData);
                    $p->carry = (bool)($data & 0x80);
                    $data = ($data << 1) & 0xFF;
                    $registers->a |= $data;
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !($registers->a & 0xFF);
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_RLA:
                    $data = ($this->readByte($addrOrData) << 1) + $p->carry;
                    $p->carry = (bool)($data & 0x100);
                    $registers->a = ($data & $registers->a) & 0xFF;
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !($registers->a & 0xFF);
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_SRE:
                    $data = $this->readByte($addrOrData);
                    $p->carry = (bool)($data & 0x01);
                    $data >>= 1;
                    $registers->a ^= $data;
                    $p->negative = (bool)($registers->a & 0x80);
                    $p->zero = !($registers->a & 0xFF);
                    $this->write($addrOrData, $data);
                    break;
                case Opcode::BASE_RRA:
                    $data = $this->readByte($addrOrData);
                    $carry = (bool)($data & 0x01);
                    $data = ($data >> 1) | ($p->carry ? 0x80 : 0x00);
                    $operated = $data + $registers->a + $carry;
                    $overflow = (!((($registers->a ^ $data) & 0x80) != 0) &&
                        ((($registers->a ^ $operated) & 0x80)) != 0);
                    $p->overflow = $overflow;
                    $p->negative = (bool)($operated & 0x80);
                    $p->zero = !($operated & 0xFF);
                    $registers->a = $operated & 0xFF;
                    $p->carry = $operated > 0xFF;
                    $this->write($addrOrData, $data);
                    break;

                default:
                    throw new \Exception(sprintf('Unknown opecode %s detected.', $ocp->mode));
            }
            $cycle += $ocp->cycle + $additionalCycle + ($this->hasBranched ? 1 : 0);
        }

        return $cycle;
    }

    private function debug($opcode)
    {
        printf(
            "Invalid opcode: %s in pc: %04x\n",
            dechex($opcode),
            $this->registers->pc
        );
        if ($this->registers->pc < 0x0800) {
            Debugger::dump($this->bus->ram->ram);
        } else {
            if ($this->registers->pc < 0x2000) {
                printf("Redirect ram: %04x\n", $this->registers->pc - 0x0800);
                Debugger::dump($this->bus->ram->ram);
            } else {
                if ($this->registers->pc < 0x4000) {
                    printf("Ppu: %04x\n", ($this->registers->pc - 0x2000) % 8);
                    Debugger::dump($this->bus->ppu->registers);
                } else {
                    if ($this->registers->pc === 0x4016) {
                        printf("Keypad\n");
                    } else {
                        if ($this->registers->pc >= 0xC000) {
                            if ($this->bus->programRom->size() <= 0x4000) {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0xC000);
                                Debugger::dump($this->bus->programRom->rom);
                            } else {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0x8000);
                                Debugger::dump($this->bus->programRom->rom);
                            }
                        } else {
                            if ($this->registers->pc >= 0x8000) {
                                printf("Redirect program rom: %04x\n", $this->registers->pc - 0x8000);
                                Debugger::dump($this->bus->programRom->rom);
                            } else {
                                printf("Something wrong...\n");
                            }
                        }
                    }
                }
            }
        }
    }
}
