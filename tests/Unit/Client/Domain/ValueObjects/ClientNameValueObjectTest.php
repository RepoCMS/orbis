<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientNameException;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use PHPUnit\Framework\TestCase;

final class ClientNameValueObjectTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $vo = new ClientNameValueObject('ACMS Solutions');
        self::assertSame('ACMS Solutions', (string) $vo);
    }

    public function testNormalizesWhitespace(): void
    {
        $vo = new ClientNameValueObject('  ACMS   Solutions  ');
        self::assertSame('ACMS Solutions', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ClientNameException::class);
        new ClientNameValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(ClientNameException::class);
        new ClientNameValueObject('A');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(ClientNameException::class);
        new ClientNameValueObject(str_repeat('a', 101));
    }

    public function testAcceptsBoundaryValues(): void
    {
        $vo = new ClientNameValueObject('Ab');
        self::assertSame('Ab', (string) $vo);

        $long = str_repeat('a', 100);
        $vo2 = new ClientNameValueObject($long);
        self::assertSame($long, (string) $vo2);
    }

    public function testFromStringFactory(): void
    {
        $vo = ClientNameValueObject::fromString('ACMS Solutions');
        self::assertSame('ACMS Solutions', (string) $vo);
    }
}
