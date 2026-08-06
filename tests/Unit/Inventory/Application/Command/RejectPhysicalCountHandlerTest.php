<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\RejectPhysicalCount\RejectPhysicalCountCommand;
use App\Inventory\Application\Command\RejectPhysicalCount\RejectPhysicalCountHandler;
use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Entities\PhysicalCountItem;
use App\Inventory\Domain\Events\PhysicalCountRejected;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class RejectPhysicalCountHandlerTest extends TestCase
{
    private const COUNT_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID = 'LOC-001';
    private const COUNTED_BY = 'User-001';
    private const REASON = 'Diferencias muy grandes, requiere re-conteo';

    private function makePendingApprovalCount(): PhysicalCount
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

        $count->submit();

        return $count;
    }

    private function makeDraftCount(): PhysicalCount
    {
        $count = PhysicalCount::start(
            id: new PhysicalCountIdValueObject('01HZ0000000000000000000099'),
            locationId: self::LOCATION_ID,
            countedBy: self::COUNTED_BY,
        );

        $count->addItem(PhysicalCountItem::record(
            id: new PhysicalCountItemIdValueObject('01HZ0000000000000000000011'),
            physicalCount: $count,
            productCode: 'ATM-001',
            expectedQty: 10,
            countedQty: 10,
        ));

        return $count;
    }

    public function testRejectsPhysicalCount(): void
    {
        $count = $this->makePendingApprovalCount();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);
        $countRepo->expects($this->once())->method('save')->with($count);

        $publishedEvents = [];
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (PhysicalCountRejected $e) use (&$publishedEvents): void {
                $publishedEvents[] = $e;
            });

        $handler = new RejectPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        ($handler)(new RejectPhysicalCountCommand(
            countId: self::COUNT_ID,
            reason: self::REASON,
        ));

        self::assertTrue($count->isRejected(), 'Count should be rejected');
        self::assertSame(self::REASON, $count->rejectionReason());
        self::assertNotNull($count->rejectedAt());

        self::assertCount(1, $publishedEvents);
        $event = $publishedEvents[0];
        self::assertSame(self::COUNT_ID, $event->countId);
        self::assertSame(self::REASON, $event->reason);
    }

    public function testThrowsWhenCountNotFound(): void
    {
        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn(null);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RejectPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('El conteo físico no existe');

        ($handler)(new RejectPhysicalCountCommand(
            countId: self::COUNT_ID,
            reason: self::REASON,
        ));
    }

    public function testThrowsWhenNotPendingApproval(): void
    {
        $count = $this->makeDraftCount();

        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->method('findById')->willReturn($count);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RejectPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(InventoryDomainException::class);
        $this->expectExceptionMessage('Solo se pueden rechazar conteos en estado pendiente de aprobación');

        ($handler)(new RejectPhysicalCountCommand(
            countId: '01HZ0000000000000000000099',
            reason: self::REASON,
        ));
    }
}
