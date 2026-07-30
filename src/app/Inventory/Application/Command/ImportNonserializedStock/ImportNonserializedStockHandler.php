<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportNonserializedStock;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\NonserializedStockStatus;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Infrastructure\Import\NonserializedStockCsvFileParser;
use App\Inventory\Infrastructure\Import\NonserializedStockRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportNonserializedStockHandler
{
    public function __construct(
        private NonserializedStockCsvFileParser $parser,
        private NonserializedStockRowValidator $rowValidator,
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function __invoke(ImportNonserializedStockCommand $command): ImportResult
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

            if ($product->isSerialized()) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: El producto "%s" es serializado. Use la importación de stock serializado.', $rowNumber, $row['product_code']);
                continue;
            }

            $locationId = new LocationIdValueObject(strtoupper(trim($row['location_id'])));
            if ($this->locationRepository->findById($locationId) === null) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Ubicación con ID "%s" no encontrada', $rowNumber, $row['location_id']);
                continue;
            }

            $quantity = (int) trim($row['quantity']);

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $productId = new ProductIdValueObject((string) $product->id());

                $existing = $this->nonserializedStockRepository->findByProductAndLocationAndStatus(
                    $productId,
                    $locationId,
                    NonserializedStockStatus::AVAILABLE,
                );

                if ($existing !== null) {
                    $existing->addStock($quantity);
                    $this->nonserializedStockRepository->save($existing);
                } else {
                    $newStock = NonserializedStock::initialize(
                        id: new NonserializedStockIdValueObject($this->idGenerator->generate()),
                        productId: $productId,
                        locationId: $locationId,
                        initialQuantity: $quantity,
                    );
                    $this->nonserializedStockRepository->save($newStock);
                }

                $movement = Movement::create(
                    id: new MovementIdValueObject($this->idGenerator->generate()),
                    productId: $productId,
                    type: MovementType::ENTRY,
                    sourceLocationId: null,
                    destinationLocationId: $locationId,
                    serializedStockId: null,
                    quantity: $quantity,
                    notes: 'Importación de stock no serializado',
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
