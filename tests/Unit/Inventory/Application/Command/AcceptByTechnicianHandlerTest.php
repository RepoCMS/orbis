<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\AcceptByTechnician\AcceptByTechnicianCommand;
use App\Inventory\Application\Command\AcceptByTechnician\AcceptByTechnicianHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class AcceptByTechnicianHandlerTest extends TestCase
{
    private const REQUEST_ID  = '01HZ0000000000000000000001';
    private const PRODUCT_ID  = '01HZ0000000000000000000003';
    private const SOURCE_LOC  = '01HZ0000000000000000000004';
    private const DEST_LOC    = '01HZ0000000000000000000005';

    public function testAcceptsNonSerializedRequest(): void
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000008'),
            productId: ProductIdValueObject::fromString(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 5,
        );

        $request = Request::submit(
            id: RequestIdValueObject::fromString(self::REQUEST_ID),
            reference: 'RQ-001',
            requestedBy: new UserIdValueObject('01HZ0000000000000000000006'),
            sourceLocationId: LocationIdValueObject::fromString(self::SOURCE_LOC),
            destinationLocationId: LocationIdValueObject::fromString(self::DEST_LOC),
            items: [$item],
        );

        $request->approve(new UserIdValueObject('01HZ0000000000000000000007'));
        $request->notifyTechnicianAcceptance();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findByIdWithItems')->willReturn($request);
        $requestRepo->expects(self::once())->method('save')->with($request);

        $handler = new AcceptByTechnicianHandler($requestRepo);

        ($handler)(new AcceptByTechnicianCommand(requestId: self::REQUEST_ID));

        self::assertSame(RequestStatus::PARTIALLY_FULFILLED, $request->status());
        self::assertCount(1, $request->items());
        self::assertTrue($request->items()[0]->isPending());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findByIdWithItems')->willReturn(null);

        $handler = new AcceptByTechnicianHandler($requestRepo);

        $this->expectException(RequestNotFoundException::class);
        ($handler)(new AcceptByTechnicianCommand(requestId: '01HZ0000000000000000000099'));
    }
}
