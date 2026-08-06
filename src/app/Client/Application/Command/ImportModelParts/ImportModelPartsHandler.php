<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportModelParts;

use App\Client\Domain\Entities\ModelPart;
use App\Client\Domain\Repositories\ModelPartRepositoryInterface;
use App\Client\Domain\ValueObjects\ModelPartIdValueObject;
use App\Client\Infrastructure\Import\ModelPartCsvFileParser;
use App\Client\Infrastructure\Import\ModelPartRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportModelPartsHandler
{
    public function __construct(
        private ModelPartCsvFileParser $parser,
        private ModelPartRowValidator $rowValidator,
        private ModelPartRepositoryInterface $modelPartRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(ImportModelPartsCommand $command): ImportResult
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

            $partNumber = trim($row['numero_parte']);
            $level = trim($row['nivel'] ?? '') !== '' ? (int) trim($row['nivel']) : 0;
            $parentPartNumber = trim($row['padre'] ?? '') !== '' ? trim($row['padre']) : null;

            $existing = $this->modelPartRepository->findByPartNumber($partNumber);
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Número de parte "%s" ya existe', $rowNumber, $partNumber);
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $part = ModelPart::register(
                    id: new ModelPartIdValueObject($this->idGenerator->generate()),
                    model: trim($row['modelo']),
                    partName: trim($row['nombre_parte']),
                    partNumber: $partNumber,
                    level: $level,
                    parentPartNumber: $parentPartNumber,
                );

                $this->modelPartRepository->save($part);
                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear parte: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
