<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactRoleException;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use PHPUnit\Framework\TestCase;

final class ContactRoleValueObjectTest extends TestCase
{
    public function testAcceptsValidRole(): void
    {
        $vo = new ContactRoleValueObject('Supervisor de ATMs');
        self::assertSame('Supervisor de ATMs', (string) $vo);
    }

    public function testNormalizesWhitespace(): void
    {
        $vo = new ContactRoleValueObject('  Supervisor   de ATMs  ');
        self::assertSame('Supervisor de ATMs', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ContactRoleException::class);
        new ContactRoleValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(ContactRoleException::class);
        new ContactRoleValueObject('A');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(ContactRoleException::class);
        new ContactRoleValueObject(str_repeat('a', 101));
    }

    public function testAcceptsBoundaryValues(): void
    {
        $vo = new ContactRoleValueObject('Ab');
        self::assertSame('Ab', (string) $vo);

        $long = str_repeat('a', 100);
        $vo2 = new ContactRoleValueObject($long);
        self::assertSame($long, (string) $vo2);
    }

    public function testFromStringFactory(): void
    {
        $vo = ContactRoleValueObject::fromString('Gerente');
        self::assertSame('Gerente', (string) $vo);
    }
}
