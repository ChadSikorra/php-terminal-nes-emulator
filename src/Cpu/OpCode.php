<?php

namespace Nes\Cpu;

class OpCode
{
    public const BASE_LDA = 1;

    public const BASE_LDX = 2;

    public const BASE_LDY = 3;

    public const BASE_STA = 4;

    public const BASE_STX = 5;

    public const BASE_STY = 6;

    public const BASE_TXA = 7;

    public const BASE_TYA = 8;

    public const BASE_TXS = 9;

    public const BASE_TAY = 10;

    public const BASE_TAX = 11;

    public const BASE_TSX = 12;

    public const BASE_PHP = 13;

    public const BASE_PLP = 14;

    public const BASE_PHA = 15;

    public const BASE_PLA = 16;

    public const BASE_ADC = 17;

    public const BASE_SBC = 18;

    public const BASE_CPX = 19;

    public const BASE_CPY = 20;

    public const BASE_CMP = 21;

    public const BASE_AND = 22;

    public const BASE_EOR = 23;

    public const BASE_ORA = 24;

    public const BASE_BIT = 25;

    public const BASE_ASL = 26;

    public const BASE_LSR = 27;

    public const BASE_ROL = 28;

    public const BASE_ROR = 29;

    public const BASE_INX = 30;

    public const BASE_INY = 31;

    public const BASE_INC = 32;

    public const BASE_DEX = 33;

    public const BASE_DEY = 34;

    public const BASE_DEC = 35;

    public const BASE_CLC = 36;

    public const BASE_CLI = 37;

    public const BASE_CLV = 38;

    public const BASE_SEC = 39;

    public const BASE_SEI = 40;

    public const BASE_NOP = 41;

    public const BASE_BRK = 42;

    public const BASE_JSR = 43;

    public const BASE_JMP = 44;

    public const BASE_RTI = 45;

    public const BASE_RTS = 46;

    public const BASE_BPL = 47;

    public const BASE_BMI = 48;

    public const BASE_BVC = 49;

    public const BASE_BVS = 50;

    public const BASE_BCC = 51;

    public const BASE_BCS = 52;

    public const BASE_BNE = 53;

    public const BASE_BEQ = 54;

    public const BASE_SED = 55;

    public const BASE_CLD = 56;

    public const BASE_NOPD = 57;

    public const BASE_NOPI = 58;

    public const BASE_LAX = 59;

    public const BASE_SAX = 60;

    public const BASE_DCP = 61;

    public const BASE_ISB = 62;

    public const BASE_SLO = 63;

    public const BASE_RLA = 64;

    public const BASE_SRE = 65;

    public const BASE_RRA = 66;

    public static array $cycles;

    public static function getOpCodes(): array
    {
        self::$cycles = [
            7, 6, 2, 8, 3, 3, 5, 5, 3, 2, 2, 2, 4, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 6, 7,
            6, 6, 2, 8, 3, 3, 5, 5, 4, 2, 2, 2, 4, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 6, 7,
            6, 6, 2, 8, 3, 3, 5, 5, 3, 2, 2, 2, 3, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 6, 7,
            6, 6, 2, 8, 3, 3, 5, 5, 4, 2, 2, 2, 5, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 6, 7,
            2, 6, 2, 6, 3, 3, 3, 3, 2, 2, 2, 2, 4, 4, 4, 4,
            2, 6, 2, 6, 4, 4, 4, 4, 2, 4, 2, 5, 5, 4, 5, 5,
            2, 6, 2, 6, 3, 3, 3, 3, 2, 2, 2, 2, 4, 4, 4, 4,
            2, 5, 2, 5, 4, 4, 4, 4, 2, 4, 2, 4, 4, 4, 4, 4,
            2, 6, 2, 8, 3, 3, 5, 5, 2, 2, 2, 2, 4, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 7, 7,
            2, 6, 3, 8, 3, 3, 5, 5, 2, 2, 2, 2, 4, 4, 6, 6,
            2, 5, 2, 8, 4, 4, 6, 6, 2, 4, 2, 7, 4, 4, 7, 7,
        ];

        // @codingStandardsIgnoreStart
        return [
            'A9' => new OpCodeProps('LDA_IMM', self::BASE_LDA, Addressing::Immediate, self::$cycles[0xA9]),
            'A5' => new OpCodeProps('LDA_ZERO', self::BASE_LDA, Addressing::ZeroPage, self::$cycles[0xA5]),
            'AD' => new OpCodeProps('LDA_ABS', self::BASE_LDA, Addressing::Absolute, self::$cycles[0xAD]),
            'B5' => new OpCodeProps('LDA_ZEROX', self::BASE_LDA, Addressing::ZeroPageX, self::$cycles[0xB5]),
            'BD' => new OpCodeProps('LDA_ABSX', self::BASE_LDA, Addressing::AbsoluteX, self::$cycles[0xBD]),
            'B9' => new OpCodeProps('LDA_ABSY', self::BASE_LDA, Addressing::AbsoluteY, self::$cycles[0xB9]),
            'A1' => new OpCodeProps('LDA_INDX', self::BASE_LDA, Addressing::PreIndexedIndirect, self::$cycles[0xA1]),
            'B1' => new OpCodeProps('LDA_INDY', self::BASE_LDA, Addressing::PostIndexedIndirect, self::$cycles[0xB1]),
            'A2' => new OpCodeProps('LDX_IMM', self::BASE_LDX, Addressing::Immediate, self::$cycles[0xA2]),
            'A6' => new OpCodeProps('LDX_ZERO', self::BASE_LDX, Addressing::ZeroPage, self::$cycles[0xA6]),
            'AE' => new OpCodeProps('LDX_ABS', self::BASE_LDX, Addressing::Absolute, self::$cycles[0xAE]),
            'B6' => new OpCodeProps('LDX_ZEROY', self::BASE_LDX, Addressing::ZeroPageY, self::$cycles[0xB6]),
            'BE' => new OpCodeProps('LDX_ABSY', self::BASE_LDX, Addressing::AbsoluteY, self::$cycles[0xBE]),
            'A0' => new OpCodeProps('LDY_IMM', self::BASE_LDY, Addressing::Immediate, self::$cycles[0xA0]),
            'A4' => new OpCodeProps('LDY_ZERO', self::BASE_LDY, Addressing::ZeroPage, self::$cycles[0xA4]),
            'AC' => new OpCodeProps('LDY_ABS', self::BASE_LDY, Addressing::Absolute, self::$cycles[0xAC]),
            'B4' => new OpCodeProps('LDY_ZEROX', self::BASE_LDY, Addressing::ZeroPageX, self::$cycles[0xB4]),
            'BC' => new OpCodeProps('LDY_ABSX', self::BASE_LDY, Addressing::AbsoluteX, self::$cycles[0xBC]),
            '85' => new OpCodeProps('STA_ZERO', self::BASE_STA, Addressing::ZeroPage, self::$cycles[0x85]),
            '8D' => new OpCodeProps('STA_ABS', self::BASE_STA, Addressing::Absolute, self::$cycles[0x8D]),
            '95' => new OpCodeProps('STA_ZEROX', self::BASE_STA, Addressing::ZeroPageX, self::$cycles[0x95]),
            '9D' => new OpCodeProps('STA_ABSX', self::BASE_STA, Addressing::AbsoluteX, self::$cycles[0x9D]),
            '99' => new OpCodeProps('STA_ABSY', self::BASE_STA, Addressing::AbsoluteY, self::$cycles[0x99]),
            '81' => new OpCodeProps('STA_INDX', self::BASE_STA, Addressing::PreIndexedIndirect, self::$cycles[0x81]),
            '91' => new OpCodeProps('STA_INDY', self::BASE_STA, Addressing::PostIndexedIndirect, self::$cycles[0x91]),
            '86' => new OpCodeProps('STX_ZERO', self::BASE_STX, Addressing::ZeroPage, self::$cycles[0x86]),
            '8E' => new OpCodeProps('STX_ABS', self::BASE_STX, Addressing::Absolute, self::$cycles[0x8E]),
            '96' => new OpCodeProps('STX_ZEROY', self::BASE_STX, Addressing::ZeroPageY, self::$cycles[0x96]),
            '84' => new OpCodeProps('STY_ZERO', self::BASE_STY, Addressing::ZeroPage, self::$cycles[0x84]),
            '8C' => new OpCodeProps('STY_ABS', self::BASE_STY, Addressing::Absolute, self::$cycles[0x8C]),
            '94' => new OpCodeProps('STY_ZEROX', self::BASE_STY, Addressing::ZeroPageX, self::$cycles[0x94]),
            '8A' => new OpCodeProps('TXA', self::BASE_TXA, Addressing::Implied, self::$cycles[0x8A]),
            '98' => new OpCodeProps('TYA', self::BASE_TYA, Addressing::Implied, self::$cycles[0x98]),
            '9A' => new OpCodeProps('TXS', self::BASE_TXS, Addressing::Implied, self::$cycles[0x9A]),
            'A8' => new OpCodeProps('TAY', self::BASE_TAY, Addressing::Implied, self::$cycles[0xA8]),
            'AA' => new OpCodeProps('TAX', self::BASE_TAX, Addressing::Implied, self::$cycles[0xAA]),
            'BA' => new OpCodeProps('TSX', self::BASE_TSX, Addressing::Implied, self::$cycles[0xBA]),
            '8' => new OpCodeProps('PHP', self::BASE_PHP, Addressing::Implied, self::$cycles[0x08]),
            '28' => new OpCodeProps('PLP', self::BASE_PLP, Addressing::Implied, self::$cycles[0x28]),
            '48' => new OpCodeProps('PHA', self::BASE_PHA, Addressing::Implied, self::$cycles[0x48]),
            '68' => new OpCodeProps('PLA', self::BASE_PLA, Addressing::Implied, self::$cycles[0x68]),
            '69' => new OpCodeProps('ADC_IMM', self::BASE_ADC, Addressing::Immediate, self::$cycles[0x69]),
            '65' => new OpCodeProps('ADC_ZERO', self::BASE_ADC, Addressing::ZeroPage, self::$cycles[0x65]),
            '6D' => new OpCodeProps('ADC_ABS', self::BASE_ADC, Addressing::Absolute, self::$cycles[0x6D]),
            '75' => new OpCodeProps('ADC_ZEROX', self::BASE_ADC, Addressing::ZeroPageX, self::$cycles[0x75]),
            '7D' => new OpCodeProps('ADC_ABSX', self::BASE_ADC, Addressing::AbsoluteX, self::$cycles[0x7D]),
            '79' => new OpCodeProps('ADC_ABSY', self::BASE_ADC, Addressing::AbsoluteY, self::$cycles[0x79]),
            '61' => new OpCodeProps('ADC_INDX', self::BASE_ADC, Addressing::PreIndexedIndirect, self::$cycles[0x61]),
            '71' => new OpCodeProps('ADC_INDY', self::BASE_ADC, Addressing::PostIndexedIndirect, self::$cycles[0x71]),
            'E9' => new OpCodeProps('SBC_IMM', self::BASE_SBC, Addressing::Immediate, self::$cycles[0xE9]),
            'E5' => new OpCodeProps('SBC_ZERO', self::BASE_SBC, Addressing::ZeroPage, self::$cycles[0xE5]),
            'ED' => new OpCodeProps('SBC_ABS', self::BASE_SBC, Addressing::Absolute, self::$cycles[0xED]),
            'F5' => new OpCodeProps('SBC_ZEROX', self::BASE_SBC, Addressing::ZeroPageX, self::$cycles[0xF5]),
            'FD' => new OpCodeProps('SBC_ABSX', self::BASE_SBC, Addressing::AbsoluteX, self::$cycles[0xFD]),
            'F9' => new OpCodeProps('SBC_ABSY', self::BASE_SBC, Addressing::AbsoluteY, self::$cycles[0xF9]),
            'E1' => new OpCodeProps('SBC_INDX', self::BASE_SBC, Addressing::PreIndexedIndirect, self::$cycles[0xE1]),
            'F1' => new OpCodeProps('SBC_INDY', self::BASE_SBC, Addressing::PostIndexedIndirect, self::$cycles[0xF1]),
            'E0' => new OpCodeProps('CPX_IMM', self::BASE_CPX, Addressing::Immediate, self::$cycles[0xE0]),
            'E4' => new OpCodeProps('CPX_ZERO', self::BASE_CPX, Addressing::ZeroPage, self::$cycles[0xE4]),
            'EC' => new OpCodeProps('CPX_ABS', self::BASE_CPX, Addressing::Absolute, self::$cycles[0xEC]),
            'C0' => new OpCodeProps('CPY_IMM', self::BASE_CPY, Addressing::Immediate, self::$cycles[0xC0]),
            'C4' => new OpCodeProps('CPY_ZERO', self::BASE_CPY, Addressing::ZeroPage, self::$cycles[0xC4]),
            'CC' => new OpCodeProps('CPY_ABS', self::BASE_CPY, Addressing::Absolute, self::$cycles[0xCC]),
            'C9' => new OpCodeProps('CMP_IMM', self::BASE_CMP, Addressing::Immediate, self::$cycles[0xC9]),
            'C5' => new OpCodeProps('CMP_ZERO', self::BASE_CMP, Addressing::ZeroPage, self::$cycles[0xC5]),
            'CD' => new OpCodeProps('CMP_ABS', self::BASE_CMP, Addressing::Absolute, self::$cycles[0xCD]),
            'D5' => new OpCodeProps('CMP_ZEROX', self::BASE_CMP, Addressing::ZeroPageX, self::$cycles[0xD5]),
            'DD' => new OpCodeProps('CMP_ABSX', self::BASE_CMP, Addressing::AbsoluteX, self::$cycles[0xDD]),
            'D9' => new OpCodeProps('CMP_ABSY', self::BASE_CMP, Addressing::AbsoluteY, self::$cycles[0xD9]),
            'C1' => new OpCodeProps('CMP_INDX', self::BASE_CMP, Addressing::PreIndexedIndirect, self::$cycles[0xC1]),
            'D1' => new OpCodeProps('CMP_INDY', self::BASE_CMP, Addressing::PostIndexedIndirect, self::$cycles[0xD1]),
            '29' => new OpCodeProps('AND_IMM', self::BASE_AND, Addressing::Immediate, self::$cycles[0x29]),
            '25' => new OpCodeProps('AND_ZERO', self::BASE_AND, Addressing::ZeroPage, self::$cycles[0x25]),
            '2D' => new OpCodeProps('AND_ABS', self::BASE_AND, Addressing::Absolute, self::$cycles[0x2D]),
            '35' => new OpCodeProps('AND_ZEROX', self::BASE_AND, Addressing::ZeroPageX, self::$cycles[0x35]),
            '3D' => new OpCodeProps('AND_ABSX', self::BASE_AND, Addressing::AbsoluteX, self::$cycles[0x3D]),
            '39' => new OpCodeProps('AND_ABSY', self::BASE_AND, Addressing::AbsoluteY, self::$cycles[0x39]),
            '21' => new OpCodeProps('AND_INDX', self::BASE_AND, Addressing::PreIndexedIndirect, self::$cycles[0x21]),
            '31' => new OpCodeProps('AND_INDY', self::BASE_AND, Addressing::PostIndexedIndirect, self::$cycles[0x31]),
            '49' => new OpCodeProps('EOR_IMM', self::BASE_EOR, Addressing::Immediate, self::$cycles[0x49]),
            '45' => new OpCodeProps('EOR_ZERO', self::BASE_EOR, Addressing::ZeroPage, self::$cycles[0x45]),
            '4D' => new OpCodeProps('EOR_ABS', self::BASE_EOR, Addressing::Absolute, self::$cycles[0x4D]),
            '55' => new OpCodeProps('EOR_ZEROX', self::BASE_EOR, Addressing::ZeroPageX, self::$cycles[0x55]),
            '5D' => new OpCodeProps('EOR_ABSX', self::BASE_EOR, Addressing::AbsoluteX, self::$cycles[0x5D]),
            '59' => new OpCodeProps('EOR_ABSY', self::BASE_EOR, Addressing::AbsoluteY, self::$cycles[0x59]),
            '41' => new OpCodeProps('EOR_INDX', self::BASE_EOR, Addressing::PreIndexedIndirect, self::$cycles[0x41]),
            '51' => new OpCodeProps('EOR_INDY', self::BASE_EOR, Addressing::PostIndexedIndirect, self::$cycles[0x51]),
            '9' => new OpCodeProps('ORA_IMM', self::BASE_ORA, Addressing::Immediate, self::$cycles[0x09]),
            '5' => new OpCodeProps('ORA_ZERO', self::BASE_ORA, Addressing::ZeroPage, self::$cycles[0x05]),
            'D' => new OpCodeProps('ORA_ABS', self::BASE_ORA, Addressing::Absolute, self::$cycles[0x0D]),
            '15' => new OpCodeProps('ORA_ZEROX', self::BASE_ORA, Addressing::ZeroPageX, self::$cycles[0x15]),
            '1D' => new OpCodeProps('ORA_ABSX', self::BASE_ORA, Addressing::AbsoluteX, self::$cycles[0x1D]),
            '19' => new OpCodeProps('ORA_ABSY', self::BASE_ORA, Addressing::AbsoluteY, self::$cycles[0x19]),
            '1' => new OpCodeProps('ORA_INDX', self::BASE_ORA, Addressing::PreIndexedIndirect, self::$cycles[0x01]),
            '11' => new OpCodeProps('ORA_INDY', self::BASE_ORA, Addressing::PostIndexedIndirect, self::$cycles[0x11]),
            '24' => new OpCodeProps('BIT_ZERO', self::BASE_BIT, Addressing::ZeroPage, self::$cycles[0x24]),
            '2C' => new OpCodeProps('BIT_ABS', self::BASE_BIT, Addressing::Absolute, self::$cycles[0x2C]),
            'A' => new OpCodeProps('ASL', self::BASE_ASL, Addressing::Accumulator, self::$cycles[0x0A]),
            '6' => new OpCodeProps('ASL_ZERO', self::BASE_ASL, Addressing::ZeroPage, self::$cycles[0x06]),
            'E' => new OpCodeProps('ASL_ABS', self::BASE_ASL, Addressing::Absolute, self::$cycles[0x0E]),
            '16' => new OpCodeProps('ASL_ZEROX', self::BASE_ASL, Addressing::ZeroPageX, self::$cycles[0x16]),
            '1E' => new OpCodeProps('ASL_ABSX', self::BASE_ASL, Addressing::AbsoluteX, self::$cycles[0x1E]),
            '4A' => new OpCodeProps('LSR', self::BASE_LSR, Addressing::Accumulator, self::$cycles[0x4A]),
            '46' => new OpCodeProps('LSR_ZERO', self::BASE_LSR, Addressing::ZeroPage, self::$cycles[0x46]),
            '4E' => new OpCodeProps('LSR_ABS', self::BASE_LSR, Addressing::Absolute, self::$cycles[0x4E]),
            '56' => new OpCodeProps('LSR_ZEROX', self::BASE_LSR, Addressing::ZeroPageX, self::$cycles[0x56]),
            '5E' => new OpCodeProps('LSR_ABSX', self::BASE_LSR, Addressing::AbsoluteX, self::$cycles[0x5E]),
            '2A' => new OpCodeProps('ROL', self::BASE_ROL, Addressing::Accumulator, self::$cycles[0x2A]),
            '26' => new OpCodeProps('ROL_ZERO', self::BASE_ROL, Addressing::ZeroPage, self::$cycles[0x26]),
            '2E' => new OpCodeProps('ROL_ABS', self::BASE_ROL, Addressing::Absolute, self::$cycles[0x2E]),
            '36' => new OpCodeProps('ROL_ZEROX', self::BASE_ROL, Addressing::ZeroPageX, self::$cycles[0x36]),
            '3E' => new OpCodeProps('ROL_ABSX', self::BASE_ROL, Addressing::AbsoluteX, self::$cycles[0x3E]),
            '6A' => new OpCodeProps('ROR', self::BASE_ROR, Addressing::Accumulator, self::$cycles[0x6A]),
            '66' => new OpCodeProps('ROR_ZERO', self::BASE_ROR, Addressing::ZeroPage, self::$cycles[0x66]),
            '6E' => new OpCodeProps('ROR_ABS', self::BASE_ROR, Addressing::Absolute, self::$cycles[0x6E]),
            '76' => new OpCodeProps('ROR_ZEROX', self::BASE_ROR, Addressing::ZeroPageX, self::$cycles[0x76]),
            '7E' => new OpCodeProps('ROR_ABSX', self::BASE_ROR, Addressing::AbsoluteX, self::$cycles[0x7E]),
            'E8' => new OpCodeProps('INX', self::BASE_INX, Addressing::Implied, self::$cycles[0xE8]),
            'C8' => new OpCodeProps('INY', self::BASE_INY, Addressing::Implied, self::$cycles[0xC8]),
            'E6' => new OpCodeProps('INC_ZERO', self::BASE_INC, Addressing::ZeroPage, self::$cycles[0xE6]),
            'EE' => new OpCodeProps('INC_ABS', self::BASE_INC, Addressing::Absolute, self::$cycles[0xEE]),
            'F6' => new OpCodeProps('INC_ZEROX', self::BASE_INC, Addressing::ZeroPageX, self::$cycles[0xF6]),
            'FE' => new OpCodeProps('INC_ABSX', self::BASE_INC, Addressing::AbsoluteX, self::$cycles[0xFE]),
            'CA' => new OpCodeProps('DEX', self::BASE_DEX, Addressing::Implied, self::$cycles[0xCA]),
            '88' => new OpCodeProps('DEY', self::BASE_DEY, Addressing::Implied, self::$cycles[0x88]),
            'C6' => new OpCodeProps('DEC_ZERO', self::BASE_DEC, Addressing::ZeroPage, self::$cycles[0xC6]),
            'CE' => new OpCodeProps('DEC_ABS', self::BASE_DEC, Addressing::Absolute, self::$cycles[0xCE]),
            'D6' => new OpCodeProps('DEC_ZEROX', self::BASE_DEC, Addressing::ZeroPageX, self::$cycles[0xD6]),
            'DE' => new OpCodeProps('DEC_ABSX', self::BASE_DEC, Addressing::AbsoluteX, self::$cycles[0xDE]),
            '18' => new OpCodeProps('CLC', self::BASE_CLC, Addressing::Implied, self::$cycles[0x18]),
            '58' => new OpCodeProps('CLI', self::BASE_CLI, Addressing::Implied, self::$cycles[0x58]),
            'B8' => new OpCodeProps('CLV', self::BASE_CLV, Addressing::Implied, self::$cycles[0xB8]),
            '38' => new OpCodeProps('SEC', self::BASE_SEC, Addressing::Implied, self::$cycles[0x38]),
            '78' => new OpCodeProps('SEI', self::BASE_SEI, Addressing::Implied, self::$cycles[0x78]),
            'EA' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xEA]),
            '0' => new OpCodeProps('BRK', self::BASE_BRK, Addressing::Implied, self::$cycles[0x00]),
            '20' => new OpCodeProps('JSR_ABS', self::BASE_JSR, Addressing::Absolute, self::$cycles[0x20]),
            '4C' => new OpCodeProps('JMP_ABS', self::BASE_JMP, Addressing::Absolute, self::$cycles[0x4C]),
            '6C' => new OpCodeProps('JMP_INDABS', self::BASE_JMP, Addressing::IndirectAbsolute, self::$cycles[0x6C]),
            '40' => new OpCodeProps('RTI', self::BASE_RTI, Addressing::Implied, self::$cycles[0x40]),
            '60' => new OpCodeProps('RTS', self::BASE_RTS, Addressing::Implied, self::$cycles[0x60]),
            '10' => new OpCodeProps('BPL', self::BASE_BPL, Addressing::Relative, self::$cycles[0x10]),
            '30' => new OpCodeProps('BMI', self::BASE_BMI, Addressing::Relative, self::$cycles[0x30]),
            '50' => new OpCodeProps('BVC', self::BASE_BVC, Addressing::Relative, self::$cycles[0x50]),
            '70' => new OpCodeProps('BVS', self::BASE_BVS, Addressing::Relative, self::$cycles[0x70]),
            '90' => new OpCodeProps('BCC', self::BASE_BCC, Addressing::Relative, self::$cycles[0x90]),
            'B0' => new OpCodeProps('BCS', self::BASE_BCS, Addressing::Relative, self::$cycles[0xB0]),
            'D0' => new OpCodeProps('BNE', self::BASE_BNE, Addressing::Relative, self::$cycles[0xD0]),
            'F0' => new OpCodeProps('BEQ', self::BASE_BEQ, Addressing::Relative, self::$cycles[0xF0]),
            'F8' => new OpCodeProps('SED', self::BASE_SED, Addressing::Implied, self::$cycles[0xF8]),
            'D8' => new OpCodeProps('CLD', self::BASE_CLD, Addressing::Implied, self::$cycles[0xD8]),
            // unofficial opecode
            // Also see https://wiki.nesdev.com/w/index.php/CPU_unofficial_opcodes
            '1A' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x1A]),
            '3A' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x3A]),
            '5A' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x5A]),
            '7A' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x7A]),
            'DA' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xDA]),
            'FA' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xFA]),

            '02' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x02]),
            '12' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x12]),
            '22' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x22]),
            '32' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x32]),
            '42' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x42]),
            '52' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x52]),
            '62' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x62]),
            '72' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x72]),
            '92' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0x92]),
            'B2' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xB2]),
            'D2' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xD2]),
            'F2' => new OpCodeProps('NOP', self::BASE_NOP, Addressing::Implied, self::$cycles[0xF2]),

            '80' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x80]),
            '82' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x82]),
            '89' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x89]),
            'C2' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0xC2]),
            'E2' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0xE2]),
            '04' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x04]),
            '44' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x44]),
            '64' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x64]),
            '14' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x14]),
            '34' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x34]),
            '54' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x54]),
            '74' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0x74]),
            'D4' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0xD4]),
            'F4' => new OpCodeProps('NOPD', self::BASE_NOPD, Addressing::Implied, self::$cycles[0xF4]),

            '0C' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0x0C]),
            '1C' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0x1C]),
            '3C' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0x3C]),
            '5C' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0x5C]),
            '7C' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0x7C]),
            'DC' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0xDC]),
            'FC' => new OpCodeProps('NOPI', self::BASE_NOPI, Addressing::Implied, self::$cycles[0xFC]),
            // LAX
            'A7' => new OpCodeProps('LAX_ZERO', self::BASE_LAX, Addressing::ZeroPage, self::$cycles[0xA7]),
            'B7' => new OpCodeProps('LAX_ZEROY', self::BASE_LAX, Addressing::ZeroPageY, self::$cycles[0xB7]),
            'AF' => new OpCodeProps('LAX_ABS', self::BASE_LAX, Addressing::Absolute, self::$cycles[0xAF]),
            'BF' => new OpCodeProps('LAX_ABSY', self::BASE_LAX, Addressing::AbsoluteY, self::$cycles[0xBF]),
            'A3' => new OpCodeProps('LAX_INDX', self::BASE_LAX, Addressing::PreIndexedIndirect, self::$cycles[0xA3]),
            'B3' => new OpCodeProps('LAX_INDY', self::BASE_LAX, Addressing::PostIndexedIndirect, self::$cycles[0xB3]),
            // SAX
            '87' => new OpCodeProps('SAX_ZERO', self::BASE_SAX, Addressing::ZeroPage, self::$cycles[0x87]),
            '97' => new OpCodeProps('SAX_ZEROY', self::BASE_SAX, Addressing::ZeroPageY, self::$cycles[0x97]),
            '8F' => new OpCodeProps('SAX_ABS', self::BASE_SAX, Addressing::Absolute, self::$cycles[0x8F]),
            '83' => new OpCodeProps('SAX_INDX', self::BASE_SAX, Addressing::PreIndexedIndirect, self::$cycles[0x83]),
            // SBC
            'EB' => new OpCodeProps('SBC_IMM', self::BASE_SBC, Addressing::Immediate, self::$cycles[0xEB]),
            // DCP
            'C7' => new OpCodeProps('DCP_ZERO', self::BASE_DCP, Addressing::ZeroPage, self::$cycles[0xC7]),
            'D7' => new OpCodeProps('DCP_ZEROX', self::BASE_DCP, Addressing::ZeroPageX, self::$cycles[0xD7]),
            'CF' => new OpCodeProps('DCP_ABS', self::BASE_DCP, Addressing::Absolute, self::$cycles[0xCF]),
            'DF' => new OpCodeProps('DCP_ABSX', self::BASE_DCP, Addressing::AbsoluteX, self::$cycles[0xDF]),
            'DB' => new OpCodeProps('DCP_ABSY', self::BASE_DCP, Addressing::AbsoluteY, self::$cycles[0xD8]),
            'C3' => new OpCodeProps('DCP_INDX', self::BASE_DCP, Addressing::PreIndexedIndirect, self::$cycles[0xC3]),
            'D3' => new OpCodeProps('DCP_INDY', self::BASE_DCP, Addressing::PostIndexedIndirect, self::$cycles[0xD3]),
            // ISB
            'E7' => new OpCodeProps('ISB_ZERO', self::BASE_ISB, Addressing::ZeroPage, self::$cycles[0xE7]),
            'F7' => new OpCodeProps('ISB_ZEROX', self::BASE_ISB, Addressing::ZeroPageX, self::$cycles[0xF7]),
            'EF' => new OpCodeProps('ISB_ABS', self::BASE_ISB, Addressing::Absolute, self::$cycles[0xEF]),
            'FF' => new OpCodeProps('ISB_ABSX', self::BASE_ISB, Addressing::AbsoluteX, self::$cycles[0xFF]),
            'FB' => new OpCodeProps('ISB_ABSY', self::BASE_ISB, Addressing::AbsoluteY, self::$cycles[0xF8]),
            'E3' => new OpCodeProps('ISB_INDX', self::BASE_ISB, Addressing::PreIndexedIndirect, self::$cycles[0xE3]),
            'F3' => new OpCodeProps('ISB_INDY', self::BASE_ISB, Addressing::PostIndexedIndirect, self::$cycles[0xF3]),
            // SLO
            '07' => new OpCodeProps('SLO_ZERO', self::BASE_SLO, Addressing::ZeroPage, self::$cycles[0x07]),
            '17' => new OpCodeProps('SLO_ZEROX', self::BASE_SLO, Addressing::ZeroPageX, self::$cycles[0x17]),
            '0F' => new OpCodeProps('SLO_ABS', self::BASE_SLO, Addressing::Absolute, self::$cycles[0x0F]),
            '1F' => new OpCodeProps('SLO_ABSX', self::BASE_SLO, Addressing::AbsoluteX, self::$cycles[0x1F]),
            '1B' => new OpCodeProps('SLO_ABSY', self::BASE_SLO, Addressing::AbsoluteY, self::$cycles[0x1B]),
            '03' => new OpCodeProps('SLO_INDX', self::BASE_SLO, Addressing::PreIndexedIndirect, self::$cycles[0x03]),
            '13' => new OpCodeProps('SLO_INDY', self::BASE_SLO, Addressing::PostIndexedIndirect, self::$cycles[0x13]),
            // RLA
            '27' => new OpCodeProps('RLA_ZERO', self::BASE_RLA, Addressing::ZeroPage, self::$cycles[0x27]),
            '37' => new OpCodeProps('RLA_ZEROX', self::BASE_RLA, Addressing::ZeroPageX, self::$cycles[0x37]),
            '2F' => new OpCodeProps('RLA_ABS', self::BASE_RLA, Addressing::Absolute, self::$cycles[0x2F]),
            '3F' => new OpCodeProps('RLA_ABSX', self::BASE_RLA, Addressing::AbsoluteX, self::$cycles[0x3F]),
            '3B' => new OpCodeProps('RLA_ABSY', self::BASE_RLA, Addressing::AbsoluteY, self::$cycles[0x3B]),
            '23' => new OpCodeProps('RLA_INDX', self::BASE_RLA, Addressing::PreIndexedIndirect, self::$cycles[0x23]),
            '33' => new OpCodeProps('RLA_INDY', self::BASE_RLA, Addressing::PostIndexedIndirect, self::$cycles[0x33]),
            // SRE
            '47' => new OpCodeProps('SRE_ZERO', self::BASE_SRE, Addressing::ZeroPage, self::$cycles[0x47]),
            '57' => new OpCodeProps('SRE_ZEROX', self::BASE_SRE, Addressing::ZeroPageX, self::$cycles[0x57]),
            '4F' => new OpCodeProps('SRE_ABS', self::BASE_SRE, Addressing::Absolute, self::$cycles[0x4F]),
            '5F' => new OpCodeProps('SRE_ABSX', self::BASE_SRE, Addressing::AbsoluteX, self::$cycles[0x5F]),
            '5B' => new OpCodeProps('SRE_ABSY', self::BASE_SRE, Addressing::AbsoluteY, self::$cycles[0x5B]),
            '43' => new OpCodeProps('SRE_INDX', self::BASE_SRE, Addressing::PreIndexedIndirect, self::$cycles[0x43]),
            '53' => new OpCodeProps('SRE_INDY', self::BASE_SRE, Addressing::PostIndexedIndirect, self::$cycles[0x53]),
            // RRA
            '67' => new OpCodeProps('RRA_ZERO', self::BASE_RRA, Addressing::ZeroPage, self::$cycles[0x67]),
            '77' => new OpCodeProps('RRA_ZEROX', self::BASE_RRA, Addressing::ZeroPageX, self::$cycles[0x77]),
            '6F' => new OpCodeProps('RRA_ABS', self::BASE_RRA, Addressing::Absolute, self::$cycles[0x6F]),
            '7F' => new OpCodeProps('RRA_ABSX', self::BASE_RRA, Addressing::AbsoluteX, self::$cycles[0x7F]),
            '7B' => new OpCodeProps('RRA_ABSY', self::BASE_RRA, Addressing::AbsoluteY, self::$cycles[0x7B]),
            '63' => new OpCodeProps('RRA_INDX', self::BASE_RRA, Addressing::PreIndexedIndirect, self::$cycles[0x63]),
            '73' => new OpCodeProps('RRA_INDY', self::BASE_RRA, Addressing::PostIndexedIndirect, self::$cycles[0x73]),
        ];
        // @codingStandardsIgnoreEnd
    }
}
