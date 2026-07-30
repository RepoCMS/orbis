<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ImportProducts;

use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Events\ProductCreated;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Infrastructure\Import\ProductCsvFileParser;
use App\Inventory\Infrastructure\Import\ProductRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportProductsHandler
{
    public function __construct(
        private ProductCsvFileParser $parser,
        private ProductRowValidator $rowValidator,
        private ProductRepositoryInterface $productRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(ImportProductsCommand $command): ImportResult
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

            $code = new ProductCodeAssignedBySupplierValueObject(trim($row['code']));

            $existing = $this->productRepository->findByCode($code);
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Código de producto %s ya existe', $rowNumber, $row['code']);
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $product = Product::add(
                    id: new ProductIdValueObject($this->idGenerator->generate()),
                    productCode: $code,
                    name: new ProductNameValueObject(trim($row['name'])),
                    description: trim($row['description'] ?? ''),
                    trackingType: ProductTrackingType::from(trim($row['tracking_type'])),
                );

                $this->productRepository->save($product);

                $this->eventPublisher->publish(new ProductCreated(
                    productId: (string) $product->id(),
                    productCode: (string) $product->productCode(),
                ));

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear producto: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
