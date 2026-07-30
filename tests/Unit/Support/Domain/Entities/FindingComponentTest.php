<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\FindingComponent;
use App\Support\Domain\ValueObjects\FindingComponentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindingComponentTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const FINDING_ID = '01HZ0000000000000000000002';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $component = FindingComponent::create(
            id: FindingComponentIdValueObject::fromString(self::ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            componentId: 'DISP-001',
        );

        self::assertSame(self::ID, (string) $component->id());
        self::assertSame(self::FINDING_ID, (string) $component->findingId());
        self::assertSame('DISP-001', $component->componentId());
    }

    public function testCreateThrowsOnEmptyComponentId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del componente es obligatorio');

        FindingComponent::create(
            id: FindingComponentIdValueObject::fromString(self::ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            componentId: '',
        );
    }
}
