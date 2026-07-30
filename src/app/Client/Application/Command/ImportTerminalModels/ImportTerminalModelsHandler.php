<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportTerminalModels;

use App\Client\Domain\Entities\TerminalModel;
use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use App\Client\Infrastructure\Import\TerminalModelCsvFileParser;
use App\Client\Infrastructure\Import\TerminalModelRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportTerminalModelsHandler
{
    public function __construct(
        private TerminalModelCsvFileParser $parser,
        private TerminalModelRowValidator $rowValidator,
        private TerminalModelRepositoryInterface $terminalModelRepository,
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function __invoke(ImportTerminalModelsCommand $command): ImportResult
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

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $model = TerminalModel::register(
                    id: new TerminalModelIdValueObject($this->idGenerator->generate()),
                    name: trim($row['name']),
                    description: trim($row['description'] ?? '') ?: null,
                );

                $this->terminalModelRepository->save($model);
                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear modelo: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
