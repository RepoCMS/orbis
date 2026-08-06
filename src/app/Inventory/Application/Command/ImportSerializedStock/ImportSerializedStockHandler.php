<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportSerializedStock;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Inventory\Infrastructure\Import\SerializedStockCsvFileParser;
use App\Inventory\Infrastructure\Import\SerializedStockRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportSerializedStockHandler
{
    public function __construct(
        private SerializedStockCsvFileParser $parser,
        private SerializedStockRowValidator $rowValidator,
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(ImportSerializedStockCommand $command): ImportResult
    {
        $rows = $this->parser->parse($command->filePath);

        if (empty($rows)) {
            return new ImportResult(0, 0, 0, ['Archivo vacío o sin datos']);
        }

        $missingHeaders = $this->parser->validateHeaders(array_keys($rows[0]));
        if ($missingHeaders !== []) {
            return new ImportResult(0, 0, 0, [
                sprintf('Cabeceras faltantes: %s', implode(', ', $missingHeaders)),
            ]);
        }

        $imported = 0;
        $errors = 0;
        $skipped = 0;
        $errorDetail = [];

        foreach ($rows as $idx => $row) {
            $rowNumber = $idx + 2;

            $validationErrors = $this->rowValidator->validate($row, $rowNumber);
            if ($validationErrors !== []) {
                $errors++;
                $errorDetail = array_merge($errorDetail, $validationErrors);
                continue;
            }

            $productCode = new ProductCodeAssignedBySupplierValueObject(trim($row['product_code']));
            $product = $this->productRepository->findByCode($productCode);
            if ($product === null) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Producto con código "%s" no encontrado', $rowNumber, $row['product_code']);
                continue;
            }

            if (!$product->isSerialized()) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: El producto "%s" no es serializado', $rowNumber, $row['product_code']);
                continue;
            }

            $locationId = new LocationIdValueObject(strtoupper(trim($row['location_id'])));
            if ($this->locationRepository->findById($locationId) === null) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Ubicación con ID "%s" no encontrada', $rowNumber, $row['location_id']);
                continue;
            }

            $serialNumber = new StockSerialNumberValueObject(trim($row['serial_number']));
            $existing = $this->serializedStockRepository->findBySerial(
                new ProductIdValueObject((string) $product->id()),
                $serialNumber,
            );
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Número de serie "%s" ya existe para este producto', $rowNumber, $row['serial_number']);
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $stock = SerializedStock::register(
                    id: new SerializedStockIdValueObject($this->idGenerator->generate()),
                    productId: new ProductIdValueObject((string) $product->id()),
                    serialNumber: $serialNumber,
                    locationId: $locationId,
                );
                $this->serializedStockRepository->save($stock);

                $movement = Movement::create(
                    id: new MovementIdValueObject($this->idGenerator->generate()),
                    productId: new ProductIdValueObject((string) $product->id()),
                    type: MovementType::ENTRY,
                    sourceLocationId: null,
                    destinationLocationId: $locationId,
                    serializedStockId: $stock->id(),
                    quantity: 1,
                    notes: 'Importación de stock serializado',
                );
                $movement->complete();
                $this->movementRepository->save($movement);

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al importar stock: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
