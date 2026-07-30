<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\SiteIdException;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use PHPUnit\Framework\TestCase;

final class SiteIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new SiteIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(SiteIdException::class);
        new SiteIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = SiteIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
