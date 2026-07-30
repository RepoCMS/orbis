<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use App\Identity\Domain\ValueObjects\TokenHashValueObject;
use PHPUnit\Framework\TestCase;

final class TokenHashValueObjectTest extends TestCase
{
    private const VALID_HEX = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';

    public function testAcceptsValidHash(): void
    {
        $vo = new TokenHashValueObject(self::VALID_HEX);
        self::assertSame(self::VALID_HEX, (string) $vo);
    }

    public function testRejectsInvalidHash(): void
    {
        $this->expectException(TokenException::class);
        new TokenHashValueObject('not-a-valid-hash');
    }

    public function testFromPlainTokenComputesSha256(): void
    {
        $plain = new PlainTokenValueObject(self::VALID_HEX);
        $hash = TokenHashValueObject::fromPlainToken($plain);

        $expected = hash('sha256', self::VALID_HEX);
        self::assertSame($expected, (string) $hash);
    }

    public function testMatchesReturnsTrueForMatchingToken(): void
    {
        $plain = new PlainTokenValueObject(self::VALID_HEX);
        $hash = TokenHashValueObject::fromPlainToken($plain);

        self::assertTrue($hash->matches($plain));
    }

    public function testMatchesReturnsFalseForDifferentToken(): void
    {
        $plain = new PlainTokenValueObject(self::VALID_HEX);
        $hash = TokenHashValueObject::fromPlainToken($plain);

        $other = new PlainTokenValueObject(
            'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff',
        );
        self::assertFalse($hash->matches($other));
    }
}
