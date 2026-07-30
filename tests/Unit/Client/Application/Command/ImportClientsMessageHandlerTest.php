<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportClients\ImportClientsHandler;
use App\Client\Application\Command\ImportClients\ImportClientsMessage;
use App\Client\Application\Command\ImportClients\ImportClientsMessageHandler;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Infrastructure\Import\ClientCsvFileParser;
use App\Client\Infrastructure\Import\ClientRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportClientsMessageHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);
        return $path;
    }

    public function testDelegatesToHandler(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n1712345675001,Test Client,Dir,,,";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn(null);
        $clientRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $innerHandler = new ImportClientsHandler(
            new ClientCsvFileParser(new CsvFileParser()),
            new ClientRowValidator(),
            $clientRepo,
            $idGenerator,
            $eventPublisher,
        );

        $messageHandler = new ImportClientsMessageHandler($innerHandler);
        ($messageHandler)(new ImportClientsMessage(filePath: $path));
    }

    public function testDelegatesWithDryRun(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n1712345675001,Test Client,Dir,,,";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->expects($this->never())->method('save');

        $innerHandler = new ImportClientsHandler(
            new ClientCsvFileParser(new CsvFileParser()),
            new ClientRowValidator(),
            $clientRepo,
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $messageHandler = new ImportClientsMessageHandler($innerHandler);
        ($messageHandler)(new ImportClientsMessage(filePath: $path, dryRun: true));
    }
}
