<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Inventory\Application\Command\SubmitRequest\SubmitRequestCommand;
use App\Inventory\Application\Command\SubmitRequest\SubmitRequestHandler;
use App\Inventory\Application\ValueObjects\RequestItemCommandInput;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class SubmitRequestHandlerTest extends TestCase
{
    private const REQUESTED_BY       = '01HZ0000000000000000000001';
    private const PRODUCT_ID         = '01HZ0000000000000000000002';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000003';
    private const SOURCE_LOC         = '01HZ0000000000000000000004';
    private const DEST_LOC           = '01HZ0000000000000000000005';
    private const GENERATED_ID       = '01JANAAAAAAAAAAAAAAAAAAAAA';

    public function testSubmitsRequestSuccessfully(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedRequest = null;
        $requestRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Request $request) use (&$savedRequest) {
                $savedRequest = $request;
                return true;
            }));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitRequestHandler($requestRepo, $eventPublisher, $idGenerator);
        ($handler)(new SubmitRequestCommand(
            items: [
                new RequestItemCommandInput(
                    productId:         self::PRODUCT_ID,
                    serializedStockId: self::SERIALIZED_STOCK_ID,
                    quantity:          2,
                ),
            ],
            sourceLocationId:       self::SOURCE_LOC,
            destinationLocationId:  self::DEST_LOC,
            requestedBy:            self::REQUESTED_BY,
            notes:                  'Solicitud urgente',
        ));

        self::assertNotNull($savedRequest);
        self::assertSame(RequestStatus::PENDING, $savedRequest->status());
        self::assertSame((string) $savedRequest->requestedBy(), self::REQUESTED_BY);
        self::assertCount(1, $savedRequest->items());
        self::assertSame((string) $savedRequest->items()[0]->productId(), self::PRODUCT_ID);
        self::assertSame(2, $savedRequest->items()[0]->quantity());
        self::assertSame(self::SERIALIZED_STOCK_ID, (string) $savedRequest->items()[0]->serializedStockId());
        self::assertTrue($savedRequest->items()[0]->isPending());
        self::assertSame('Solicitud urgente', $savedRequest->notes());
    }

    public function testGeneratesReferenceWithYearAndSuffix(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedRequest = null;
        $requestRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Request $request) use (&$savedRequest) {
                $savedRequest = $request;
                return true;
            }));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitRequestHandler($requestRepo, $eventPublisher, $idGenerator);
        ($handler)(new SubmitRequestCommand(
            items: [
                new RequestItemCommandInput(
                    productId:         self::PRODUCT_ID,
                    serializedStockId: null,
                    quantity:          1,
                ),
            ],
            sourceLocationId:       self::SOURCE_LOC,
            destinationLocationId:  self::DEST_LOC,
            requestedBy:            self::REQUESTED_BY,
            notes:                  null,
        ));

        self::assertNotNull($savedRequest);
        self::assertMatchesRegularExpression(
            '/^SOL-\d{4}-[A-Z0-9]{6}$/',
            $savedRequest->reference(),
        );
        self::assertStringContainsString('AAAAAA', $savedRequest->reference());
    }

    public function testSubmitsWithoutSerializedStock(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedRequest = null;
        $requestRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Request $request) use (&$savedRequest) {
                $savedRequest = $request;
                return true;
            }));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitRequestHandler($requestRepo, $eventPublisher, $idGenerator);
        ($handler)(new SubmitRequestCommand(
            items: [
                new RequestItemCommandInput(
                    productId:         self::PRODUCT_ID,
                    serializedStockId: null,
                    quantity:          5,
                ),
            ],
            sourceLocationId:       self::SOURCE_LOC,
            destinationLocationId:  self::DEST_LOC,
            requestedBy:            self::REQUESTED_BY,
            notes:                  null,
        ));

        self::assertNotNull($savedRequest);
        self::assertCount(1, $savedRequest->items());
        self::assertNull($savedRequest->items()[0]->serializedStockId());
        self::assertSame(5, $savedRequest->items()[0]->quantity());
        self::assertNull($savedRequest->notes());
    }

    public function testSubmitsWithoutNotes(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $savedRequest = null;
        $requestRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Request $request) use (&$savedRequest) {
                $savedRequest = $request;
                return true;
            }));

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new SubmitRequestHandler($requestRepo, $eventPublisher, $idGenerator);
        ($handler)(new SubmitRequestCommand(
            items: [
                new RequestItemCommandInput(
                    productId:         self::PRODUCT_ID,
                    serializedStockId: null,
                    quantity:          3,
                ),
            ],
            sourceLocationId:       self::SOURCE_LOC,
            destinationLocationId:  self::DEST_LOC,
            requestedBy:            self::REQUESTED_BY,
            notes:                  null,
        ));

        self::assertNotNull($savedRequest);
        self::assertNull($savedRequest->notes());
    }
}
