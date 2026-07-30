<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalNameException;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use PHPUnit\Framework\TestCase;

final class TerminalNameValueObjectTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $vo = new TerminalNameValueObject('Cajero Centro');
        self::assertSame('Cajero Centro', (string) $vo);
    }

    public function testNormalizesWhitespace(): void
    {
        $vo = new TerminalNameValueObject('  Cajero   Centro  ');
        self::assertSame('Cajero Centro', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(TerminalNameException::class);
        new TerminalNameValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(TerminalNameException::class);
        new TerminalNameValueObject('A');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(TerminalNameException::class);
        new TerminalNameValueObject(str_repeat('a', 101));
    }

    public function testAcceptsBoundaryValues(): void
    {
        $vo = new TerminalNameValueObject('Ab');
        self::assertSame('Ab', (string) $vo);

        $long = str_repeat('a', 100);
        $vo2 = new TerminalNameValueObject($long);
        self::assertSame($long, (string) $vo2);
    }

    public function testFromStringFactory(): void
    {
        $vo = TerminalNameValueObject::fromString('Cajero Norte');
        self::assertSame('Cajero Norte', (string) $vo);
    }
}
