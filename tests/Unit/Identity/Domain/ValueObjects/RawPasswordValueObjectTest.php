<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserPasswordException;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use PHPUnit\Framework\TestCase;

final class RawPasswordValueObjectTest extends TestCase
{
    public function testAcceptsValidPassword(): void
    {
        $vo = new RawPasswordValueObject('SecureP@ss1');
        self::assertSame('SecureP@ss1', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('Abc1@');
    }

    public function testRejectsMissingUppercase(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('securepass1@');
    }

    public function testRejectsMissingLowercase(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('SECUREPASS1@');
    }

    public function testRejectsMissingDigit(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('SecurePass@');
    }

    public function testRejectsMissingSpecialChar(): void
    {
        $this->expectException(UserPasswordException::class);
        new RawPasswordValueObject('SecurePass1');
    }

    public function testPreservesWhitespace(): void
    {
        $vo = new RawPasswordValueObject(' Secure P@ss1 ');
        self::assertSame(' Secure P@ss1 ', (string) $vo);
    }
}
