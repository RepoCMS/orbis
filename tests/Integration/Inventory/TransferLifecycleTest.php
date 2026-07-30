<?php

declare(strict_types=1);

namespace App\Tests\Integration\Inventory;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifica el ciclo de vida completo de una transferencia de stock
 * serializado a través del ORM (repositorios + entidades).
 *
 * 1. Crear ubicación origen y destino.
 * 2. Crear producto serializado.
 * 3. Crear stock serializado en origen.
 * 4. Reservar stock (simula CreateTransfer).
 * 5. Verificar stock reservado en origen.
 * 6. Completar transferencia (simula ConfirmTransfer).
 * 7. Verificar stock movido a destino.
 */
#[Group('integration')]
final class TransferLifecycleTest extends IntegrationTestCase
{
    private const SOURCE_LOCATION_ID      = '01J8XQ9W6K0000000000000010';
    private const DEST_LOCATION_ID        = '01J8XQ9W6K0000000000000011';
    private const PRODUCT_ID              = '01J8XQ9W6K0000000000000012';
    private const SERIALIZED_STOCK_ID     = '01J8XQ9W6K0000000000000013';
    private const MOVEMENT_ID             = '01J8XQ9W6K0000000000000014';
    private const SERIAL_NUMBER           = 'SN-INTEGRATION-001';

    public function testFullSerializedTransferLifecycle(): void
    {
        $em = $this->getEntityManager();

        // ── 1. Crear ubicaciones ───────────────────────────────────────────
        $sourceLocation = Location::create(
            id:      new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            name:    new LocationNameValueObject('Almacén Central'),
            city:    'Guayaquil',
            address: 'Av. Principal 100',
            type:    LocationType::WAREHOUSE,
        );

        $destLocation = Location::create(
            id:      new LocationIdValueObject(self::DEST_LOCATION_ID),
            name:    new LocationNameValueObject('Bodega Técnicos Norte'),
            city:    'Quito',
            address: 'Calle Secundaria 200',
            type:    LocationType::WAREHOUSE,
        );

        $this->persistAndFlush($sourceLocation);

        // Necesitamos persistir source primero, luego destino
        $em->persist($destLocation);
        $em->flush();

        // ── 2. Crear producto serializado ──────────────────────────────────
        $product = Product::add(
            id:                        new ProductIdValueObject(self::PRODUCT_ID),
            productCode:               new ProductCodeAssignedBySupplierValueObject('MOTOR-001'),
            name:                      new ProductNameValueObject('Motor Dispensador'),
            description:               'Motor para dispensador de billetes modelo X7',
            trackingType:              ProductTrackingType::SERIALIZED,
            lastPurchasePrice:         '150.50',
        );

        $em->persist($product);
        $em->flush();

        // ── 3. Crear stock serializado en origen ───────────────────────────
        $stock = SerializedStock::register(
            id:           new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            productId:    new ProductIdValueObject(self::PRODUCT_ID),
            serialNumber: new StockSerialNumberValueObject(self::SERIAL_NUMBER),
            locationId:   new LocationIdValueObject(self::SOURCE_LOCATION_ID),
        );

        $em->persist($stock);
        $em->flush();
        $em->clear();

        // ── 4. Verificar estado inicial del stock ──────────────────────────
        /** @var SerializedStock $reloadedStock */
        $reloadedStock = $em->getRepository(SerializedStock::class)->find(self::SERIALIZED_STOCK_ID);
        self::assertNotNull($reloadedStock, 'Stock serializado debe existir en BD');
        self::assertTrue($reloadedStock->isAvailable(), 'Stock debe iniciar AVAILABLE');
        self::assertSame(
            self::SOURCE_LOCATION_ID,
            (string) $reloadedStock->locationId(),
            'Stock debe estar en ubicación origen'
        );

        // ── 5. Simular CreateTransfer: reservar stock y crear Movement ─────
        $reloadedStock->reserve();

        /** @var IdGeneratorInterface $idGenerator */
        $idGenerator = self::getContainer()->get(IdGeneratorInterface::class);

        $movement = Movement::create(
            id:                    new MovementIdValueObject(self::MOVEMENT_ID),
            productId:             new ProductIdValueObject(self::PRODUCT_ID),
            type:                  MovementType::TRANSFER,
            sourceLocationId:      new LocationIdValueObject(self::SOURCE_LOCATION_ID),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOCATION_ID),
            serializedStockId:     new SerializedStockIdValueObject(self::SERIALIZED_STOCK_ID),
            quantity:              1,
            notes:                 'Transferencia integración test',
            createdByUserId:       (string) $idGenerator->generate(),
        );

        $movement->startTransit();

        $em->persist($reloadedStock);
        $em->persist($movement);
        $em->flush();
        $em->clear();

        // ── 6. Verificar stock reservado en origen ─────────────────────────
        /** @var SerializedStock $reservedStock */
        $reservedStock = $em->getRepository(SerializedStock::class)->find(self::SERIALIZED_STOCK_ID);
        self::assertNotNull($reservedStock, 'Stock reservado debe existir en BD');
        self::assertSame(SerializedStockStatus::RESERVED, $reservedStock->status(), 'Stock debe estar RESERVED');
        self::assertFalse($reservedStock->isAvailable(), 'Stock no debe estar disponible');
        self::assertSame(
            self::SOURCE_LOCATION_ID,
            (string) $reservedStock->locationId(),
            'Stock reservado debe mantener ubicación origen'
        );

        /** @var Movement $pendingMovement */
        $pendingMovement = $em->getRepository(Movement::class)->find(self::MOVEMENT_ID);
        self::assertNotNull($pendingMovement, 'Movement debe existir en BD');
        self::assertTrue($pendingMovement->isInTransit(), 'Movement debe estar IN_TRANSIT');
        self::assertSame(MovementType::TRANSFER, $pendingMovement->type());

        // ── 7. Simular ConfirmTransfer: completar transferencia ────────────
        $reservedStock->completeTransfer(new LocationIdValueObject(self::DEST_LOCATION_ID));
        $pendingMovement->complete();

        $em->persist($reservedStock);
        $em->persist($pendingMovement);
        $em->flush();
        $em->clear();

        // ── 8. Verificar stock movido a destino ────────────────────────────
        /** @var SerializedStock $completedStock */
        $completedStock = $em->getRepository(SerializedStock::class)->find(self::SERIALIZED_STOCK_ID);
        self::assertNotNull($completedStock, 'Stock completado debe existir en BD');
        self::assertSame(SerializedStockStatus::AVAILABLE, $completedStock->status(), 'Stock debe volver a AVAILABLE');
        self::assertTrue($completedStock->isAvailable(), 'Stock debe estar disponible');
        self::assertSame(
            self::DEST_LOCATION_ID,
            (string) $completedStock->locationId(),
            'Stock debe estar en ubicación destino'
        );

        /** @var Movement $completedMovement */
        $completedMovement = $em->getRepository(Movement::class)->find(self::MOVEMENT_ID);
        self::assertNotNull($completedMovement, 'Movement completado debe existir en BD');
        self::assertTrue($completedMovement->isCompleted(), 'Movement debe estar COMPLETED');
        self::assertSame(MovementStatus::COMPLETED, $completedMovement->status());
        self::assertNotNull($completedMovement->completedAt(), 'Movement debe tener completedAt');
    }

    /**
     * Verifica que un stock no disponible no pueda ser reservado.
     */
    public function testCannotReserveAlreadyReservedStock(): void
    {
        $em = $this->getEntityManager();

        // Crear ubicación
        $location = Location::create(
            id:      new LocationIdValueObject('01J8XQ9W6K0000000000000015'),
            name:    new LocationNameValueObject('Ubicación Test'),
            city:    'Test',
            address: 'Test 123',
            type:    LocationType::WAREHOUSE,
        );
        $em->persist($location);

        // Crear producto
        $product = Product::add(
            id:              new ProductIdValueObject('01J8XQ9W6K0000000000000016'),
            productCode:     new ProductCodeAssignedBySupplierValueObject('TEST-002'),
            name:            new ProductNameValueObject('Producto Test'),
            description:     'Para test de reserva doble',
            trackingType:    ProductTrackingType::SERIALIZED,
        );
        $em->persist($product);

        // Crear stock
        $stock = SerializedStock::register(
            id:           new SerializedStockIdValueObject('01J8XQ9W6K0000000000000017'),
            productId:    new ProductIdValueObject('01J8XQ9W6K0000000000000016'),
            serialNumber: new StockSerialNumberValueObject('SN-DOUBLE-RESERVE'),
            locationId:   new LocationIdValueObject('01J8XQ9W6K0000000000000015'),
        );
        $em->persist($stock);
        $em->flush();

        // Reservar primera vez
        $stock->reserve();
        $em->flush();

        // Intentar reservar de nuevo debe lanzar excepción
        $this->expectException(\App\Inventory\Domain\Exceptions\StockReservedException::class);

        // Recargar desde BD
        $em->clear();
        /** @var SerializedStock $reloaded */
        $reloaded = $em->getRepository(SerializedStock::class)->find('01J8XQ9W6K0000000000000017');
        $reloaded->reserve(); // Debe fallar porque ya está RESERVED
    }

    /**
     * Verifica que se pueda liberar una reserva.
     */
    public function testReleaseReservation(): void
    {
        $em = $this->getEntityManager();

        // Crear ubicación
        $location = Location::create(
            id:      new LocationIdValueObject('01J8XQ9W6K0000000000000018'),
            name:    new LocationNameValueObject('Ubicación Release'),
            city:    'Test',
            address: 'Test 456',
            type:    LocationType::WAREHOUSE,
        );
        $em->persist($location);

        // Crear producto
        $product = Product::add(
            id:              new ProductIdValueObject('01J8XQ9W6K0000000000000019'),
            productCode:     new ProductCodeAssignedBySupplierValueObject('TEST-003'),
            name:            new ProductNameValueObject('Producto Release'),
            description:     'Para test de liberación de reserva',
            trackingType:    ProductTrackingType::SERIALIZED,
        );
        $em->persist($product);

        // Crear stock
        $stock = SerializedStock::register(
            id:           new SerializedStockIdValueObject('01J8XQ9W6K0000000000000020'),
            productId:    new ProductIdValueObject('01J8XQ9W6K0000000000000019'),
            serialNumber: new StockSerialNumberValueObject('SN-RELEASE-TEST'),
            locationId:   new LocationIdValueObject('01J8XQ9W6K0000000000000018'),
        );
        $em->persist($stock);
        $em->flush();

        // Reservar
        $stock->reserve();
        $em->flush();
        self::assertSame(SerializedStockStatus::RESERVED, $stock->status());

        // Liberar
        $stock->release();
        $em->flush();

        // Verificar que volvió a AVAILABLE
        $em->clear();
        /** @var SerializedStock $released */
        $released = $em->getRepository(SerializedStock::class)->find('01J8XQ9W6K0000000000000020');
        self::assertTrue($released->isAvailable(), 'Stock debe estar disponible tras release');
        self::assertSame(SerializedStockStatus::AVAILABLE, $released->status());
    }
}
