<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactIdException;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use PHPUnit\Framework\TestCase;

final class ContactIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new ContactIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(ContactIdException::class);
        new ContactIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = ContactIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
