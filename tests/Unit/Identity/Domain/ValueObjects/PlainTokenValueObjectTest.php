<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use PHPUnit\Framework\TestCase;

final class PlainTokenValueObjectTest extends TestCase
{
    private const VALID_HEX = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';

    public function testAcceptsValidHex(): void
    {
        $vo = new PlainTokenValueObject(self::VALID_HEX);
        self::assertSame(self::VALID_HEX, (string) $vo);
    }

    public function testRejectsInvalidHex(): void
    {
        $this->expectException(TokenException::class);
        new PlainTokenValueObject('not-a-valid-hex-string');
    }

    public function testRejectsWrongLength(): void
    {
        $this->expectException(TokenException::class);
        new PlainTokenValueObject('abcdef');
    }

    public function testRejectsUppercaseHex(): void
    {
        $this->expectException(TokenException::class);
        new PlainTokenValueObject(strtoupper(self::VALID_HEX));
    }

    public function testGenerateCreatesValidToken(): void
    {
        $vo = PlainTokenValueObject::generate();
        self::assertSame(64, strlen((string) $vo));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $vo);
    }
}
