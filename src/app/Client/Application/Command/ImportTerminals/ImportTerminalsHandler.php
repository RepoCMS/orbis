<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportTerminals;

use App\Client\Application\Command\RegisterTerminal\RegisterTerminalCommand;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\SerialForTerminalAlreadyExistsException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Infrastructure\Import\TerminalCsvFileParser;
use App\Client\Infrastructure\Import\TerminalRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Exceptions\DomainException;

final readonly class ImportTerminalsHandler
{
    public function __construct(
        private TerminalCsvFileParser $parser,
        private TerminalRowValidator $rowValidator,
        private ClientRepositoryInterface $clientRepository,
        private RegisterTerminalHandler $registerTerminal,
    ) {}

    public function __invoke(ImportTerminalsCommand $command): ImportResult
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

            $ruc = new ClientRucValueObject(trim($row['ruc']));
            $client = $this->clientRepository->findByRuc($ruc);

            if ($client === null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Cliente con RUC %s no encontrado', $rowNumber, $row['ruc']);
                continue;
            }

            $clientId = (string) $client->id();

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                ($this->registerTerminal)(new RegisterTerminalCommand(
                    clientId: $clientId,
                    serial: trim($row['serial']),
                    vendor: trim($row['vendor']),
                    funcionality: trim($row['funcionality']),
                    type: trim($row['type']),
                ));

                $imported++;
            } catch (ClientNotFoundException $e) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Cliente no encontrado: %s', $rowNumber, $e->getMessage());
            } catch (SerialForTerminalAlreadyExistsException $e) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Serial %s ya existe para este cliente', $rowNumber, $row['serial']);
            } catch (DomainException | \InvalidArgumentException $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
