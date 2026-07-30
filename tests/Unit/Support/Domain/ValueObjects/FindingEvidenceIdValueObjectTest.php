<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\ValueObjects;

use App\Support\Domain\Exceptions\FindingEvidenceException;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindingEvidenceIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new FindingEvidenceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(FindingEvidenceException::class);
        new FindingEvidenceIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = FindingEvidenceIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
