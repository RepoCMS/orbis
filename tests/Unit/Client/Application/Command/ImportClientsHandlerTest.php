<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportClients\ImportClientsCommand;
use App\Client\Application\Command\ImportClients\ImportClientsHandler;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Events\ClientCreated;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Infrastructure\Import\ClientCsvFileParser;
use App\Client\Infrastructure\Import\ClientRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportClientsHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);
        return $path;
    }

    private function makeHandler(
        ?ClientRepositoryInterface $clientRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
        ?DomainEventPublisherInterface $eventPublisher = null,
    ): ImportClientsHandler {
        return new ImportClientsHandler(
            new ClientCsvFileParser(new CsvFileParser()),
            new ClientRowValidator(),
            $clientRepo ?? $this->createMock(ClientRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
            $eventPublisher ?? $this->createMock(DomainEventPublisherInterface::class),
        );
    }

    public function testImportsClientsSuccessfully(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n1712345675001,Cliente Uno,Dir 1,c1@test.com,0991111111\n1712345675001,Cliente Dos,Dir 2,c2@test.com,0992222222";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn(null);
        $clientRepo->expects($this->exactly(2))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->exactly(2))->method('publish')
            ->with($this->isInstanceOf(ClientCreated::class));

        $handler = $this->makeHandler($clientRepo, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportClientsCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n1712345675001,Cliente Uno,Dir 1,,,\n1700000001001,Cliente Dos,Dir 2,,,";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($clientRepo);
        $result = ($handler)(new ImportClientsCommand(filePath: $path, dryRun: true));

        self::assertSame(2, $result->imported);
    }

    public function testSkipsDuplicateRuc(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n1712345675001,Duplicado,Dir 1,,,";
        $path = $this->createTempCsv($csv);

        $existingClient = Client::createForTest(
            new ClientIdValueObject(self::GENERATED_ID),
            new ClientNameValueObject('Existente'),
            new ClientRucValueObject('1712345675001'),
            '',
            ClientStatus::ACTIVE,
        );

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($existingClient);
        $clientRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($clientRepo);
        $result = ($handler)(new ImportClientsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = "ruc,nombre,direccion,email,telefono\n,invalid,Dir 1,,,\n12345,Short RUC,Dir 2,,,";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->expects($this->never())->method('save');

        $handler = $this->makeHandler($clientRepo);
        $result = ($handler)(new ImportClientsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(2, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $path = $this->createTempCsv("ruc,nombre,direccion,email,telefono\n");

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportClientsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportClientsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
