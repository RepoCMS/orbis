<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\FindTransformationById\FindTransformationByIdHandler;
use App\Inventory\Application\Query\Response\TransformationResponse;
use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\Repositories\TransformationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use PHPUnit\Framework\TestCase;

final class FindTransformationByIdHandlerTest extends TestCase
{
    private const TRANSFORMATION_ID  = '01HZ0000000000000000000010';
    private const SERIALIZED_STOCK_ID = '01HZ0000000000000000000030';
    private const LABORATORY_ID      = '01HZ0000000000000000000002';
    private const PERFORMED_BY       = '01HZ0000000000000000000040';
    private const ORIGINAL_PRODUCT_ID = '01HZ0000000000000000000001';
    private const NEW_PRODUCT_ID     = '01HZ0000000000000000000003';
    private const ORIGINAL_SERIAL    = 'SN-ORIGINAL-001';
    private const NEW_SERIAL         = 'SN-NEW-001';

    private function makeTransformation(
        bool $withProductChange = false,
        bool $withSerialChange = false,
    ): Transformation {
        return Transformation::record(
            new TransformationIdValueObject(self::TRANSFORMATION_ID),
            new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            new LocationIdValueObject(self::LABORATORY_ID),
            new UserIdValueObject(self::PERFORMED_BY),
            new ProductIdValueObject(self::ORIGINAL_PRODUCT_ID),
            $withProductChange ? new ProductIdValueObject(self::NEW_PRODUCT_ID) : null,
            new StockSerialNumberValueObject(self::ORIGINAL_SERIAL),
            $withSerialChange ? new StockSerialNumberValueObject(self::NEW_SERIAL) : null,
            $withSerialChange ? 'Transformación con cambio de serial' : 'Transformación simple',
        );
    }

    private function makeHandler(?Transformation $transformation = null): FindTransformationByIdHandler
    {
        $repo = $this->createMock(TransformationRepositoryInterface::class);
        $repo->method('findById')->willReturn($transformation);

        return new FindTransformationByIdHandler($repo);
    }

    public function testReturnsTransformationResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeTransformation());

        $response = ($handler)(self::TRANSFORMATION_ID);

        self::assertInstanceOf(TransformationResponse::class, $response);
        self::assertSame(self::TRANSFORMATION_ID, $response->id);
        self::assertSame(self::SERIALIZED_STOCK_ID, $response->serializedStockId);
        self::assertSame(self::LABORATORY_ID, $response->laboratoryId);
        self::assertSame(self::PERFORMED_BY, $response->performedById);
        self::assertSame(self::ORIGINAL_PRODUCT_ID, $response->originalProductId);
        self::assertNull($response->newProductId);
        self::assertSame(self::ORIGINAL_SERIAL, $response->originalSerialNumber);
        self::assertNull($response->newSerialNumber);
        self::assertFalse($response->changedProduct);
        self::assertFalse($response->changedSerial);
        self::assertSame('Transformación simple', $response->notes);
        self::assertNotNull($response->createdAt);
    }

    public function testReturnsTransformationWithProductAndSerialChanges(): void
    {
        $handler = $this->makeHandler($this->makeTransformation(
            withProductChange: true,
            withSerialChange: true,
        ));

        $response = ($handler)(self::TRANSFORMATION_ID);

        self::assertSame(self::NEW_PRODUCT_ID, $response->newProductId);
        self::assertSame(self::NEW_SERIAL, $response->newSerialNumber);
        self::assertTrue($response->changedProduct);
        self::assertTrue($response->changedSerial);
        self::assertSame('Transformación con cambio de serial', $response->notes);
    }

    public function testReturnsTransformationWithOnlySerialChange(): void
    {
        $handler = $this->makeHandler($this->makeTransformation(
            withProductChange: false,
            withSerialChange: true,
        ));

        $response = ($handler)(self::TRANSFORMATION_ID);

        self::assertNull($response->newProductId);
        self::assertSame(self::NEW_SERIAL, $response->newSerialNumber);
        self::assertFalse($response->changedProduct);
        self::assertTrue($response->changedSerial);
    }

    public function testThrowsRuntimeExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Transformación con ID "%s" no encontrada.', self::TRANSFORMATION_ID));

        ($handler)(self::TRANSFORMATION_ID);
    }
}
