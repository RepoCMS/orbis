<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\StartPhysicalCount\StartPhysicalCountCommand;
use App\Inventory\Application\Command\StartPhysicalCount\StartPhysicalCountHandler;
use App\Inventory\Domain\Events\PhysicalCountStarted;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class StartPhysicalCountHandlerTest extends TestCase
{
    private const COUNT_ID = '01HZ0000000000000000000001';
    private const LOCATION_ID = 'LOC-001';
    private const COUNTED_BY = 'User-001';

    public function testStartsPhysicalCountWithItems(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(
                self::COUNT_ID,
                '01HZ0000000000000000000010',
                '01HZ0000000000000000000011',
            );

        $savedCount = null;
        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($count) use (&$savedCount): void {
                $savedCount = $count;
            });

        $publishedEvents = [];
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (PhysicalCountStarted $e) use (&$publishedEvents): void {
                $publishedEvents[] = $e;
            });

        $handler = new StartPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $countId = ($handler)(new StartPhysicalCountCommand(
            locationId: self::LOCATION_ID,
            countedBy: self::COUNTED_BY,
            notes: 'Test count',
            items: [
                [
                    'productCode' => 'ATM-001',
                    'expectedQty' => 10,
                    'countedQty' => 8,
                    'notes' => null,
                ],
                [
                    'productCode' => 'PAPER-001',
                    'expectedQty' => 5,
                    'countedQty' => 5,
                    'notes' => 'OK',
                ],
            ],
        ));

        self::assertSame(self::COUNT_ID, $countId);

        self::assertNotNull($savedCount);
        self::assertSame(self::LOCATION_ID, $savedCount->locationId());
        self::assertSame(self::COUNTED_BY, $savedCount->countedBy());
        self::assertTrue($savedCount->isDraft());
        self::assertCount(2, $savedCount->items());

        self::assertCount(1, $publishedEvents);
        $event = $publishedEvents[0];
        self::assertSame(self::COUNT_ID, $event->countId);
        self::assertSame(self::LOCATION_ID, $event->locationId);
        self::assertSame(self::COUNTED_BY, $event->countedBy);
    }

    public function testStartsPhysicalCountWithoutItems(): void
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::COUNT_ID);

        $savedCount = null;
        $countRepo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $countRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($count) use (&$savedCount): void {
                $savedCount = $count;
            });

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new StartPhysicalCountHandler(
            physicalCountRepository: $countRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $countId = ($handler)(new StartPhysicalCountCommand(
            locationId: self::LOCATION_ID,
            countedBy: self::COUNTED_BY,
            notes: null,
            items: [],
        ));

        self::assertSame(self::COUNT_ID, $countId);
        self::assertNotNull($savedCount);
        self::assertCount(0, $savedCount->items());
    }
}
