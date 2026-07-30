<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserPasswordException;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use PHPUnit\Framework\TestCase;

final class HashedPasswordValueObjectTest extends TestCase
{
    public function testAcceptsValidHash(): void
    {
        $vo = new HashedPasswordValueObject('$2y$12$abcdefghijklmnopqrstuv');
        self::assertSame('$2y$12$abcdefghijklmnopqrstuv', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(UserPasswordException::class);
        new HashedPasswordValueObject('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(UserPasswordException::class);
        new HashedPasswordValueObject('   ');
    }

    public function testRejectsEmptyAfterTrim(): void
    {
        $this->expectException(UserPasswordException::class);
        new HashedPasswordValueObject("\t\n\r");
    }
}
