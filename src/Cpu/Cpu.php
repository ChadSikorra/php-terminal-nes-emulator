<?php

declare(strict_types=1);

namespace Nes\Cpu;

use Exception;
use Nes\Bus\CpuBus;
use Nes\Cpu\Registers\Registers;
use Nes\Debugger;

class Cpu
{
    private Registers $registers;

    private bool $hasBranched;

    private CpuBus $bus;

    /**
     * @var OpCodeProps[]
     */
    private array $opCodeList;

    private Interrupts $interrupts;

    public function __construct(CpuBus $bus, Interrupts $interrupts)
    {
        $this->bus = $bus;
        $this->interrupts = $interrupts;

        $this->registers = Registers::getDefault();
        $this->hasBranched = false;
        $this->opCodeList = [];

        $opCodes = OpCode::getOpCodes();
        foreach ($opCodes as $key => $op) {
            $this->opCodeList[hexdec((string)$key)] = $op;
        }
    }

    /**
     * @throws Exception
     */
    public function run(): int
    {
        if ($this->interrupts->isNmiAssert()) {
            $this->processNmi();
        }
        if ($this->interrupts->isIrqAssert()) {
            $this->processIrq();
        }
        $opcode = $this->fetchByte($this->registers->pc);
        $ocp = $this->opCodeList[$opcode];
        list($addrOrData, $additionalCycle) = $this->getAddrOrDataWithAdditionalCycle($ocp->mode);
        $this->execInstruction($ocp, $addrOrData);

        return $ocp->cycle + $additionalCycle + ($this->hasBranched ? 1 : 0);
    }

    public function reset(): void
    {
        $this->registers = Registers::getDefault();
        // TODO: flownes set 0x8000 to PC when read(0xfffc) fails.
        $this->registers->pc = $this->readWord(0xFFFC);
        printf("Initial pc: %04x\n", $this->registers->pc);
    }

    /**
     * @throws Exception
     *
     * @return int[]
     */
    private function getAddrOrDataWithAdditionalCycle(int $mode): array
    {
        switch ($mode) {
            case Addressing::Implied:
            case Addressing::Accumulator:
                return [0x00, 0];
            case Addressing::ZeroPage:
            case Addressing::Immediate:
                return [$this->fetchByte($this->registers->pc), 0];
            case Addressing::Relative:
                $baseAddr = $this->fetchByte($this->registers->pc);
                $addr = $baseAddr < 0x80 ? $baseAddr + $this->registers->pc : $baseAddr + $this->registers->pc - 256;

                return [
                    $addr,
                    ($addr & 0xff00) !== ($this->registers->pc & 0xFF00) ? 1 : 0,
                ];
            case Addressing::ZeroPageX:
                $addr = $this->fetchByte($this->registers->pc);

                return [
                    ($addr + $this->registers->x) & 0xff,
                    0,
                ];
            case Addressing::ZeroPageY:
                $addr = $this->fetchByte($this->registers->pc);

                return [($addr + $this->registers->y & 0xff), 0];
            case Addressing::Absolute:
                return [($this->fetchWord($this->registers->pc)), 0];
            case Addressing::AbsoluteX:
                $addr = ($this->fetchWord($this->registers->pc));
                $additionalCycle = ($addr & 0xFF00) !== (($addr + $this->registers->x) & 0xFF00) ? 1 : 0;

                return [($addr + $this->registers->x) & 0xFFFF, $additionalCycle];
            case Addressing::AbsoluteY:
                $addr = ($this->fetchWord($this->registers->pc));
                $additionalCycle = ($addr & 0xFF00) !== (($addr + $this->registers->y) & 0xFF00) ? 1 : 0;

                return [($addr + $this->registers->y) & 0xFFFF, $additionalCycle];
            case Addressing::PreIndexedIndirect:
                $baseAddr = ($this->fetchByte($this->registers->pc) + $this->registers->x) & 0xFF;
                $addr = $this->readByte($baseAddr) + ($this->readByte(($baseAddr + 1) & 0xFF) << 8);

                return [
                    $addr & 0xFFFF,
                    ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0,
                ];
            case Addressing::PostIndexedIndirect:
                $addrOrData = $this->fetchByte($this->registers->pc);
                $baseAddr = $this->readByte($addrOrData) + ($this->readByte(($addrOrData + 1) & 0xFF) << 8);
                $addr = $baseAddr + $this->registers->y;

                return [
                    $addr & 0xFFFF,
                    ($addr & 0xFF00) !== ($baseAddr & 0xFF00) ? 1 : 0,
                ];
            case Addressing::IndirectAbsolute:
                $addrOrData = $this->fetchWord($this->registers->pc);
                $addr = $this->readByte($addrOrData) +
                    ($this->readByte(($addrOrData & 0xFF00) | ((($addrOrData & 0xFF) + 1) & 0xFF)) << 8);

                return [$addr & 0xFFFF, 0];
            default:
                echo $mode;

                throw new Exception(shell_exec("Unknown addressing {$mode} detected."));
        }
    }

    private function write(int $addr, int $data): void
    {
        $this->bus->writeByCpu($addr, $data);
    }

    private function push(int $data): void
    {
        $this->write(0x100 | ($this->registers->sp & 0xFF), $data);
        --$this->registers->sp;
    }

    private function pop(): int
    {
        ++$this->registers->sp;

        return $this->readByte(0x100 | ($this->registers->sp & 0xFF));
    }

    private function branch(int $addr): void
    {
        $this->registers->pc = $addr;
        $this->hasBranched = true;
    }

    public function pushStatus(): void
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

    private function popStatus(): void
    {
        $status = $this->pop();
        $this->registers->p->negative = (bool) ($status & 0x80);
        $this->registers->p->overflow = (bool) ($status & 0x40);
        $this->registers->p->reserved = (bool) ($status & 0x20);
        $this->registers->p->break_mode = (bool) ($status & 0x10);
        $this->registers->p->decimal_mode = (bool) ($status & 0x08);
        $this->registers->p->interrupt = (bool) ($status & 0x04);
        $this->registers->p->zero = (bool) ($status & 0x02);
        $this->registers->p->carry = (bool) ($status & 0x01);
    }

    private function popPC(): void
    {
        $this->registers->pc = $this->pop();
        $this->registers->pc += ($this->pop() << 8);
    }

    /**
     * @throws Exception
     */
    private function execInstruction(OpCodeProps $ocp, int $addrOrData): void
    {
        $this->hasBranched = false;
        switch ($ocp->baseType) {
            case OpCode::BASE_LDA:
                $this->registers->a = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;

                break;
            case OpCode::BASE_LDX:
                $this->registers->x = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $this->registers->p->negative = (bool) ($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;

                break;
            case OpCode::BASE_LDY:
                $this->registers->y = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $this->registers->p->negative = (bool) ($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;

                break;
            case OpCode::BASE_STA:
                $this->write($addrOrData, $this->registers->a);

                break;
            case OpCode::BASE_STX:
                $this->write($addrOrData, $this->registers->x);

                break;
            case OpCode::BASE_STY:
                $this->write($addrOrData, $this->registers->y);

                break;
            case OpCode::BASE_TAX:
                $this->registers->x = $this->registers->a;
                $this->registers->p->negative = (bool) ($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;

                break;
            case OpCode::BASE_TAY:
                $this->registers->y = $this->registers->a;
                $this->registers->p->negative = (bool) ($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;

                break;
            case OpCode::BASE_TSX:
                $this->registers->x = $this->registers->sp & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;

                break;
            case OpCode::BASE_TXA:
                $this->registers->a = $this->registers->x;
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;

                break;
            case OpCode::BASE_TXS:
                $this->registers->sp = $this->registers->x + 0x0100;

                break;
            case OpCode::BASE_TYA:
                $this->registers->a = $this->registers->y;
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;

                break;
            case OpCode::BASE_ADC:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $operated = $data + $this->registers->a + $this->registers->p->carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0)
                    && ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated > 0xFF;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;

                break;
            case OpCode::BASE_AND:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $operated = $data & $this->registers->a;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;

                break;
            case OpCode::BASE_ASL:
                if (Addressing::Accumulator == $ocp->mode) {
                    $acc = $this->registers->a;
                    $this->registers->p->carry = (bool) ($acc & 0x80);
                    $this->registers->a = ($acc << 1) & 0xFF;
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                } else {
                    $data = $this->readByte($addrOrData);
                    $this->registers->p->carry = (bool) ($data & 0x80);
                    $shifted = ($data << 1) & 0xFF;
                    $this->write($addrOrData, $shifted);
                    $this->registers->p->zero = !$shifted;
                    $this->registers->p->negative = (bool) ($shifted & 0x80);
                }

                break;
            case OpCode::BASE_BIT:
                $data = $this->readByte($addrOrData);
                $this->registers->p->negative = (bool) ($data & 0x80);
                $this->registers->p->overflow = (bool) ($data & 0x40);
                $this->registers->p->zero = !($this->registers->a & $data);

                break;
            case OpCode::BASE_CMP:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $compared = $this->registers->a - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = (bool) ($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);

                break;
            case OpCode::BASE_CPX:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $compared = $this->registers->x - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = (bool) ($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);

                break;
            case OpCode::BASE_CPY:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $compared = $this->registers->y - $data;
                $this->registers->p->carry = $compared >= 0;
                $this->registers->p->negative = (bool) ($compared & 0x80);
                $this->registers->p->zero = !($compared & 0xff);

                break;
            case OpCode::BASE_DEC:
                $data = ($this->readByte($addrOrData) - 1) & 0xFF;
                $this->registers->p->negative = (bool) ($data & 0x80);
                $this->registers->p->zero = !$data;
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_DEX:
                $this->registers->x = ($this->registers->x - 1) & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;

                break;
            case OpCode::BASE_DEY:
                $this->registers->y = ($this->registers->y - 1) & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;

                break;
            case OpCode::BASE_EOR:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $operated = $data ^ $this->registers->a;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;

                break;
            case OpCode::BASE_INC:
                $data = ($this->readByte($addrOrData) + 1) & 0xFF;
                $this->registers->p->negative = (bool) ($data & 0x80);
                $this->registers->p->zero = !$data;
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_INX:
                $this->registers->x = ($this->registers->x + 1) & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->x & 0x80);
                $this->registers->p->zero = !$this->registers->x;

                break;
            case OpCode::BASE_INY:
                $this->registers->y = ($this->registers->y + 1) & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->y & 0x80);
                $this->registers->p->zero = !$this->registers->y;

                break;
            case OpCode::BASE_LSR:
                if (Addressing::Accumulator == $ocp->mode) {
                    $acc = $this->registers->a & 0xFF;
                    $this->registers->p->carry = (bool) ($acc & 0x01);
                    $this->registers->a = $acc >> 1;
                    $this->registers->p->zero = !$this->registers->a;
                } else {
                    $data = $this->readByte($addrOrData);
                    $this->registers->p->carry = (bool) ($data & 0x01);
                    $this->registers->p->zero = !($data >> 1);
                    $this->write($addrOrData, $data >> 1);
                }
                $this->registers->p->negative = false;

                break;
            case OpCode::BASE_ORA:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $operated = $data | $this->registers->a;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !$operated;
                $this->registers->a = $operated & 0xFF;

                break;
            case OpCode::BASE_ROL:
                if (Addressing::Accumulator == $ocp->mode) {
                    $acc = $this->registers->a;
                    $this->registers->a = ($acc << 1) & 0xFF | ($this->registers->p->carry ? 0x01 : 0x00);
                    $this->registers->p->carry = (bool) ($acc & 0x80);
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                } else {
                    $data = $this->readByte($addrOrData);
                    $writeData = ($data << 1 | ($this->registers->p->carry ? 0x01 : 0x00)) & 0xFF;
                    $this->write($addrOrData, $writeData);
                    $this->registers->p->carry = (bool) ($data & 0x80);
                    $this->registers->p->zero = !$writeData;
                    $this->registers->p->negative = (bool) ($writeData & 0x80);
                }

                break;
            case OpCode::BASE_ROR:
                if (Addressing::Accumulator == $ocp->mode) {
                    $acc = $this->registers->a;
                    $this->registers->a = $acc >> 1 | ($this->registers->p->carry ? 0x80 : 0x00);
                    $this->registers->p->carry = (bool) ($acc & 0x01);
                    $this->registers->p->zero = !$this->registers->a;
                    $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                } else {
                    $data = $this->readByte($addrOrData);
                    $writeData = $data >> 1 | ($this->registers->p->carry ? 0x80 : 0x00);
                    $this->write($addrOrData, $writeData);
                    $this->registers->p->carry = (bool) ($data & 0x01);
                    $this->registers->p->zero = !$writeData;
                    $this->registers->p->negative = (bool) ($writeData & 0x80);
                }

                break;
            case OpCode::BASE_SBC:
                $data = (Addressing::Immediate == $ocp->mode) ? $addrOrData : $this->readByte($addrOrData);
                $operated = $this->registers->a - $data - ($this->registers->p->carry ? 0 : 1);
                $overflow = ((($this->registers->a ^ $operated) & 0x80) != 0
                    && (($this->registers->a ^ $data) & 0x80) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated >= 0;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;

                break;
            case OpCode::BASE_PHA:
                $this->push($this->registers->a);

                break;
            case OpCode::BASE_PHP:
                $this->registers->p->break_mode = true;
                $this->pushStatus();

                break;
            case OpCode::BASE_PLA:
                $this->registers->a = $this->pop();
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;

                break;
            case OpCode::BASE_PLP:
                $this->popStatus();
                $this->registers->p->reserved = true;

                break;
            case OpCode::BASE_JMP:
                $this->registers->pc = $addrOrData;

                break;
            case OpCode::BASE_JSR:
                $pc = $this->registers->pc - 1;
                $this->push(($pc >> 8) & 0xFF);
                $this->push($pc & 0xFF);
                $this->registers->pc = $addrOrData;

                break;
            case OpCode::BASE_RTS:
                $this->popPC();
                ++$this->registers->pc;

                break;
            case OpCode::BASE_RTI:
                $this->popStatus();
                $this->popPC();
                $this->registers->p->reserved = true;

                break;
            case OpCode::BASE_BCC:
                if (!$this->registers->p->carry) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BCS:
                if ($this->registers->p->carry) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BEQ:
                if ($this->registers->p->zero) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BMI:
                if ($this->registers->p->negative) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BNE:
                if (!$this->registers->p->zero) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BPL:
                if (!$this->registers->p->negative) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BVS:
                if ($this->registers->p->overflow) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_BVC:
                if (!$this->registers->p->overflow) {
                    $this->branch($addrOrData);
                }

                break;
            case OpCode::BASE_CLD:
                $this->registers->p->decimal_mode = false;

                break;
            case OpCode::BASE_CLC:
                $this->registers->p->carry = false;

                break;
            case OpCode::BASE_CLI:
                $this->registers->p->interrupt = false;

                break;
            case OpCode::BASE_CLV:
                $this->registers->p->overflow = false;

                break;
            case OpCode::BASE_SEC:
                $this->registers->p->carry = true;

                break;
            case OpCode::BASE_SEI:
                $this->registers->p->interrupt = true;

                break;
            case OpCode::BASE_SED:
                $this->registers->p->decimal_mode = true;

                break;
            case OpCode::BASE_BRK:
                $interrupt = $this->registers->p->interrupt;
                ++$this->registers->pc;
                $this->push(($this->registers->pc >> 8) & 0xFF);
                $this->push($this->registers->pc & 0xFF);
                $this->registers->p->break_mode = true;
                $this->pushStatus();
                $this->registers->p->interrupt = true;
                // Ignore interrupt when already set.
                if (!$interrupt) {
                    $this->registers->pc = $this->readWord(0xFFFE);
                }
                --$this->registers->pc;

                break;
            case OpCode::BASE_NOP:
                break;
            // Unofficial Opecode
            case OpCode::BASE_NOPD:
                $this->registers->pc++;

                break;
            case OpCode::BASE_NOPI:
                $this->registers->pc += 2;

                break;
            case OpCode::BASE_LAX:
                $this->registers->a = $this->registers->x = $this->readByte($addrOrData);
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !$this->registers->a;

                break;
            case OpCode::BASE_SAX:
                $operated = $this->registers->a & $this->registers->x;
                $this->write($addrOrData, $operated);

                break;
            case OpCode::BASE_DCP:
                $operated = ($this->readByte($addrOrData) - 1) & 0xFF;
                $this->registers->p->negative = (bool) ((($this->registers->a - $operated) & 0x1FF) & 0x80);
                $this->registers->p->zero = !(($this->registers->a - $operated) & 0x1FF);
                $this->write($addrOrData, $operated);

                break;
            case OpCode::BASE_ISB:
                $data = ($this->readByte($addrOrData) + 1) & 0xFF;
                $operated = (~$data & 0xFF) + $this->registers->a + $this->registers->p->carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0)
                    && ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->carry = $operated > 0xFF;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_SLO:
                $data = $this->readByte($addrOrData);
                $this->registers->p->carry = (bool) ($data & 0x80);
                $data = ($data << 1) & 0xFF;
                $this->registers->a |= $data;
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_RLA:
                $data = ($this->readByte($addrOrData) << 1) + $this->registers->p->carry;
                $this->registers->p->carry = (bool) ($data & 0x100);
                $this->registers->a = ($data & $this->registers->a) & 0xFF;
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_SRE:
                $data = $this->readByte($addrOrData);
                $this->registers->p->carry = (bool) ($data & 0x01);
                $data >>= 1;
                $this->registers->a ^= $data;
                $this->registers->p->negative = (bool) ($this->registers->a & 0x80);
                $this->registers->p->zero = !($this->registers->a & 0xFF);
                $this->write($addrOrData, $data);

                break;
            case OpCode::BASE_RRA:
                $data = $this->readByte($addrOrData);
                $carry = (bool) ($data & 0x01);
                $data = ($data >> 1) | ($this->registers->p->carry ? 0x80 : 0x00);
                $operated = $data + $this->registers->a + $carry;
                $overflow = (!((($this->registers->a ^ $data) & 0x80) != 0)
                    && ((($this->registers->a ^ $operated) & 0x80)) != 0);
                $this->registers->p->overflow = $overflow;
                $this->registers->p->negative = (bool) ($operated & 0x80);
                $this->registers->p->zero = !($operated & 0xFF);
                $this->registers->a = $operated & 0xFF;
                $this->registers->p->carry = $operated > 0xFF;
                $this->write($addrOrData, $data);

                break;

            default:
                throw new Exception(sprintf('Unknown opcode %s detected.', $ocp->baseType));
        }
    }

    private function processNmi(): void
    {
        $this->interrupts->deassertNmi();
        $this->registers->p->break_mode = false;
        $this->push(($this->registers->pc >> 8) & 0xFF);
        $this->push($this->registers->pc & 0xFF);
        $this->pushStatus();
        $this->registers->p->interrupt = true;
        $this->registers->pc = $this->readWord(0xFFFA);
    }

    private function processIrq(): void
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

    private function fetchByte(int $addr): int
    {
        ++$this->registers->pc;

        return $this->bus->readByCpu($addr & 0xFFFF);
    }

    private function fetchWord(int $addr): int
    {
        $this->registers->pc += 2;

        return $this->readWord($addr);
    }

    private function readByte(int $addr): int
    {
        $addr &= 0xFFFF;

        return $this->bus->readByCpu($addr);
    }

    private function readWord(int $addr): int
    {
        $addr &= 0xFFFF;

        return $this->bus->readByCpu($addr) | $this->bus->readByCpu($addr + 1) << 8;
    }

    private function debug(int $opcode): void
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
                    if (0x4016 === $this->registers->pc) {
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
