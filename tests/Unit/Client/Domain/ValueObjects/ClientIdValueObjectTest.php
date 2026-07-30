<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientIdException;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use PHPUnit\Framework\TestCase;

final class ClientIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new ClientIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(ClientIdException::class);
        new ClientIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = ClientIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
