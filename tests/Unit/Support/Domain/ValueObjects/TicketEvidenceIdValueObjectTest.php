<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\ValueObjects;

use App\Support\Domain\Exceptions\TicketEvidenceIdException;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketEvidenceIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new TicketEvidenceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(TicketEvidenceIdException::class);
        new TicketEvidenceIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = TicketEvidenceIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
