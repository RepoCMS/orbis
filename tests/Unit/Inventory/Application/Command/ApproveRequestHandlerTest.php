<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\ApproveRequest\ApproveRequestCommand;
use App\Inventory\Application\Command\ApproveRequest\ApproveRequestHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Events\RequestAwaitingTechnicianAcceptance;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class ApproveRequestHandlerTest extends TestCase
{
    private const REQUEST_ID   = '01HZ0000000000000000000001';
    private const APPROVED_BY  = '01HZ0000000000000000000002';
    private const PRODUCT_ID   = '01HZ0000000000000000000003';
    private const SOURCE_LOC   = '01HZ0000000000000000000004';
    private const DEST_LOC     = '01HZ0000000000000000000005';
    private const REQUESTED_BY = '01HZ0000000000000000000006';
    private const ITEM_ID      = '01HZ0000000000000000000007';

    private function makePendingRequest(): Request
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000007'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 1,
        );

        return Request::submit(
            id:                   new RequestIdValueObject(self::REQUEST_ID),
            reference:            'SOL-2026-000001',
            requestedBy:          new UserIdValueObject(self::REQUESTED_BY),
            sourceLocationId:     new LocationIdValueObject(self::SOURCE_LOC),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOC),
            notes:                null,
            items:                [$item],
        );
    }

    public function testApproveTransitionsToAwaitingTechnicianAcceptance(): void
    {
        $request = $this->makePendingRequest();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $publisher   = $this->createMock(DomainEventPublisherInterface::class);

        $requestRepo->method('findById')->willReturn($request);

        $requestRepo->expects($this->once())->method('save')->with($request);

        $publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(RequestAwaitingTechnicianAcceptance::class));

        $handler = new ApproveRequestHandler($requestRepo, $publisher);
        ($handler)(new ApproveRequestCommand(
            requestId:  self::REQUEST_ID,
            approvedBy: self::APPROVED_BY,
        ));

        self::assertSame(RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE, $request->status());
        self::assertSame(self::APPROVED_BY, (string) $request->approvedBy());
        self::assertNotNull($request->approvedAt());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $publisher   = $this->createMock(DomainEventPublisherInterface::class);

        $requestRepo->method('findById')->willReturn(null);
        $requestRepo->expects($this->never())->method('save');
        $publisher->expects($this->never())->method('publish');

        $handler = new ApproveRequestHandler($requestRepo, $publisher);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage(self::REQUEST_ID);

        ($handler)(new ApproveRequestCommand(
            requestId:  self::REQUEST_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }

    public function testApproveAlreadyApprovedRequestThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->approve(new UserIdValueObject(self::APPROVED_BY));

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $publisher   = $this->createMock(DomainEventPublisherInterface::class);

        $requestRepo->method('findById')->willReturn($request);

        $handler = new ApproveRequestHandler($requestRepo, $publisher);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden aprobar solicitudes en estado PENDING');

        ($handler)(new ApproveRequestCommand(
            requestId:  self::REQUEST_ID,
            approvedBy: self::APPROVED_BY,
        ));
    }

    public function testRejectRequestSetsRejectedStatus(): void
    {
        $request = $this->makePendingRequest();

        $request->reject(
            new UserIdValueObject(self::APPROVED_BY),
            'Stock insuficiente',
        );

        self::assertSame(RequestStatus::REJECTED, $request->status());
        self::assertSame(self::APPROVED_BY, (string) $request->rejectedBy());
        self::assertSame('Stock insuficiente', $request->rejectionReason());
        self::assertNotNull($request->rejectedAt());
    }

    public function testRejectAlreadyRejectedThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->reject(new UserIdValueObject(self::APPROVED_BY), 'Razón');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden rechazar solicitudes en estado PENDING');

        $request->reject(new UserIdValueObject(self::APPROVED_BY), 'Segunda razón');
    }
}
