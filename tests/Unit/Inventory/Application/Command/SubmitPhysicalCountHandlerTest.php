<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\SubmitPhysicalCount\SubmitPhysicalCountCommand;
use App\Inventory\Application\Command\SubmitPhysicalCount\SubmitPhysicalCountHandler;
use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Entities\PhysicalCountItem;
use App\Inventory\Domain\Enums\PhysicalCountStatus;
use App\Inventory\Domain\Events\PhysicalCountSubmitted;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class SubmitPhysicalCountHandlerTest extends TestCase
{
    private const COUNT_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID = 'LOC-001';
    private const COUNTED_BY = 'User-001';

    private function makeDraftCountWithItems(): PhysicalCount
    {
        $count = PhysicalCount::start(
            id: new PhysicalCountIdValueObject(self::COUNT_ID),
            locationId: self::LOCATION_ID,
            countedBy: self::COUNTED_BY,
            notes: 'Test count',
        );

        $count->addItem(PhysicalCountItem::record(
            id: new PhysicalCountItemIdValueObject('01HZ0000000000000000000010'),
            physicalCount: $count,
            productCode: 'ATM-001',
            expectedQty: 10,
            countedQty: 8,
        ));

        return $count;
    }

    public function testSubmitsPhysicalCount(): void
    {
        $count = $this->makeDraftCountWithItems();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);
        $countRepo->expects($this->once())->method('save')->with($count);

        $publishedEvents = [];
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (PhysicalCountSubmitted $e) use (&$publishedEvents): void {
                $publishedEvents[] = $e;
            });

        $handler = new SubmitPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        ($handler)(new SubmitPhysicalCountCommand(
            countId: self::COUNT_ID,
        ));

        self::assertTrue($count->isPendingApproval(), 'Count should become PENDING_APPROVAL after submit');

        self::assertCount(1, $publishedEvents);
        $event = $publishedEvents[0];
        self::assertSame(self::COUNT_ID, $event->countId);
        self::assertSame(self::LOCATION_ID, $event->locationId);
        self::assertSame(1, $event->itemCount);
    }

    public function testThrowsWhenCountNotFound(): void
    {
        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn(null);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('El conteo físico no existe');

        ($handler)(new SubmitPhysicalCountCommand(
            countId: self::COUNT_ID,
        ));
    }

    public function testThrowsWhenNotDraft(): void
    {
        $count = $this->makeDraftCountWithItems();
        $count->submit();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se pueden enviar conteos en estado borrador');

        ($handler)(new SubmitPhysicalCountCommand(
            countId: self::COUNT_ID,
        ));
    }
}
