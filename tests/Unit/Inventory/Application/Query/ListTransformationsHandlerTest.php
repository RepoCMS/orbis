<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\ListTransformations\ListTransformationsHandler;
use App\Inventory\Application\Query\Response\TransformationResponse;
use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTransformationsHandlerTest extends TestCase
{
    private const TRANSFORMATION_ID_1 = '01HZ0000000000000000000001';
    private const TRANSFORMATION_ID_2 = '01HZ0000000000000000000002';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000010';
    private const LABORATORY_ID       = '01HZ0000000000000000000020';
    private const PERFORMED_BY_ID     = '01HZ0000000000000000000030';
    private const ORIGINAL_PRODUCT_ID = '01HZ0000000000000000000040';
    private const NEW_PRODUCT_ID      = '01HZ0000000000000000000041';

    private function makeTransformation(
        string $id,
        bool $changedProduct,
        bool $changedSerial,
    ): Transformation {
        return Transformation::record(
            id:                   new TransformationIdValueObject($id),
            serializedStockId:    new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            laboratoryId:         new LocationIdValueObject(self::LABORATORY_ID),
            performedById:        new UserIdValueObject(self::PERFORMED_BY_ID),
            originalProductId:    new ProductIdValueObject(self::ORIGINAL_PRODUCT_ID),
            newProductId:         $changedProduct ? new ProductIdValueObject(self::NEW_PRODUCT_ID) : null,
            originalSerialNumber: new StockSerialNumberValueObject('SN-001'),
            newSerialNumber:      $changedSerial ? new StockSerialNumberValueObject('SN-002') : null,
            notes:                null,
        );
    }

    public function testReturnsAllTransformations(): void
    {
        $t1 = $this->makeTransformation(self::TRANSFORMATION_ID_1, true, false);
        $t2 = $this->makeTransformation(self::TRANSFORMATION_ID_2, false, true);

        $repo = $this->createMock(TransformationRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([$t1, $t2]);

        $handler = new ListTransformationsHandler($repo);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(TransformationResponse::class, $result[0]);
        self::assertInstanceOf(TransformationResponse::class, $result[1]);
    }

    public function testReturnsEmptyArrayWhenNoTransformations(): void
    {
        $repo = $this->createMock(TransformationRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListTransformationsHandler($repo);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFieldsForProductChange(): void
    {
        $t = $this->makeTransformation(self::TRANSFORMATION_ID_1, true, false);

        $repo = $this->createMock(TransformationRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$t]);

        $handler = new ListTransformationsHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::TRANSFORMATION_ID_1, $result[0]->id);
        self::assertSame(self::SERIALIZED_STOCK_ID, $result[0]->serializedStockId);
        self::assertSame(self::LABORATORY_ID, $result[0]->laboratoryId);
        self::assertSame(self::PERFORMED_BY_ID, $result[0]->performedById);
        self::assertSame(self::ORIGINAL_PRODUCT_ID, $result[0]->originalProductId);
        self::assertSame(self::NEW_PRODUCT_ID, $result[0]->newProductId);
        self::assertSame('SN-001', $result[0]->originalSerialNumber);
        self::assertNull($result[0]->newSerialNumber);
        self::assertNull($result[0]->notes);
        self::assertTrue($result[0]->changedProduct);
        self::assertFalse($result[0]->changedSerial);
    }

    public function testResponseContainsExpectedFieldsForSerialChange(): void
    {
        $t = $this->makeTransformation(self::TRANSFORMATION_ID_2, false, true);

        $repo = $this->createMock(TransformationRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$t]);

        $handler = new ListTransformationsHandler($repo);
        $result  = ($handler)();

        self::assertCount(1, $result);
        self::assertSame(self::TRANSFORMATION_ID_2, $result[0]->id);
        self::assertNull($result[0]->newProductId);
        self::assertSame('SN-001', $result[0]->originalSerialNumber);
        self::assertSame('SN-002', $result[0]->newSerialNumber);
        self::assertFalse($result[0]->changedProduct);
        self::assertTrue($result[0]->changedSerial);
    }
}
