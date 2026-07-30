<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportClients;

use App\Client\Domain\Entities\Client;
use App\Client\Domain\Events\ClientCreated;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Infrastructure\Import\ClientCsvFileParser;
use App\Client\Infrastructure\Import\ClientRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportClientsHandler
{
    public function __construct(
        private ClientCsvFileParser $parser,
        private ClientRowValidator $rowValidator,
        private ClientRepositoryInterface $clientRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(ImportClientsCommand $command): ImportResult
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

            $existing = $this->clientRepository->findByRuc($ruc);
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: RUC %s ya existe (cliente: %s)', $rowNumber, $row['ruc'], $existing->name());
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $client = Client::register(
                    id: new ClientIdValueObject($this->idGenerator->generate()),
                    name: new ClientNameValueObject(trim($row['nombre'])),
                    ruc: $ruc,
                    address: trim($row['direccion'] ?? ''),
                );

                $this->clientRepository->save($client);

                $this->eventPublisher->publish(new ClientCreated(
                    clientId: (string) $client->id(),
                    clientName: (string) $client->name(),
                ));

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear cliente: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
