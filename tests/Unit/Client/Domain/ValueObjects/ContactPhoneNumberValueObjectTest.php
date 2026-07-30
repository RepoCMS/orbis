<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactPhoneNumberException;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use PHPUnit\Framework\TestCase;

final class ContactPhoneNumberValueObjectTest extends TestCase
{
    public function testAcceptsValidPhone(): void
    {
        $vo = new ContactPhoneNumberValueObject('0991234567');
        self::assertSame('0991234567', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new ContactPhoneNumberValueObject('  0991234567  ');
        self::assertSame('0991234567', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ContactPhoneNumberException::class);
        new ContactPhoneNumberValueObject('');
    }

    public function testRejectsDoesNotStartWith09(): void
    {
        $this->expectException(ContactPhoneNumberException::class);
        new ContactPhoneNumberValueObject('1234567890');
    }

    public function testRejectsWrongLength(): void
    {
        $this->expectException(ContactPhoneNumberException::class);
        new ContactPhoneNumberValueObject('099123456');
    }

    public function testRejectsNonNumeric(): void
    {
        $this->expectException(ContactPhoneNumberException::class);
        new ContactPhoneNumberValueObject('09912A4567');
    }

    public function testFromStringFactory(): void
    {
        $vo = ContactPhoneNumberValueObject::fromString('0991234567');
        self::assertSame('0991234567', (string) $vo);
    }
}
