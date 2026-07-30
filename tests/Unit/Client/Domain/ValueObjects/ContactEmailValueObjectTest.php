<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactEmailException;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use PHPUnit\Framework\TestCase;

final class ContactEmailValueObjectTest extends TestCase
{
    public function testAcceptsValidEmail(): void
    {
        $vo = new ContactEmailValueObject('test@example.com');
        self::assertSame('test@example.com', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new ContactEmailValueObject('  TEST@Example.COM  ');
        self::assertSame('test@example.com', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ContactEmailException::class);
        new ContactEmailValueObject('');
    }

    public function testRejectsInvalidFormat(): void
    {
        $this->expectException(ContactEmailException::class);
        new ContactEmailValueObject('not-an-email');
    }

    public function testFromStringFactory(): void
    {
        $vo = ContactEmailValueObject::fromString('test@example.com');
        self::assertSame('test@example.com', (string) $vo);
    }
}
