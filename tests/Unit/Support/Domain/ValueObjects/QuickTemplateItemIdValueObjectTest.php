<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\ValueObjects;

use App\Support\Domain\Exceptions\QuickTemplateItemIdException;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class QuickTemplateItemIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new QuickTemplateItemIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(QuickTemplateItemIdException::class);
        new QuickTemplateItemIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = QuickTemplateItemIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
