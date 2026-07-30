<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalIdAssignedByClientException;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use PHPUnit\Framework\TestCase;

final class TerminalIdAssignedByClientValueObjectTest extends TestCase
{
    public function testAcceptsValidId(): void
    {
        $vo = new TerminalIdAssignedByClientValueObject('IDCLI001');
        self::assertSame('IDCLI001', (string) $vo);
    }

    public function testUppercasesValue(): void
    {
        $vo = new TerminalIdAssignedByClientValueObject('idcli001');
        self::assertSame('IDCLI001', (string) $vo);
    }

    public function testNormalizesWhitespace(): void
    {
        $vo = new TerminalIdAssignedByClientValueObject('  IDCLI001  ');
        self::assertSame('IDCLI001', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(TerminalIdAssignedByClientException::class);
        new TerminalIdAssignedByClientValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(TerminalIdAssignedByClientException::class);
        new TerminalIdAssignedByClientValueObject('AB');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(TerminalIdAssignedByClientException::class);
        new TerminalIdAssignedByClientValueObject(str_repeat('A', 26));
    }

    public function testRejectsSpecialCharacters(): void
    {
        $this->expectException(TerminalIdAssignedByClientException::class);
        new TerminalIdAssignedByClientValueObject('ID CLI-01');
    }

    public function testAcceptsBoundaryValues(): void
    {
        $vo = new TerminalIdAssignedByClientValueObject('ABCDE');
        self::assertSame('ABCDE', (string) $vo);

        $long = str_repeat('A', 25);
        $vo2 = new TerminalIdAssignedByClientValueObject($long);
        self::assertSame($long, (string) $vo2);
    }

    public function testFromStringFactory(): void
    {
        $vo = TerminalIdAssignedByClientValueObject::fromString('IDCLI001');
        self::assertSame('IDCLI001', (string) $vo);
    }
}
