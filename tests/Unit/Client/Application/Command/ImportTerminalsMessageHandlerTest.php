<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportTerminals\ImportTerminalsHandler;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsMessage;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsMessageHandler;
use App\Client\Application\Command\RegisterTerminal\RegisterTerminalHandler;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Enums\ClientStatus;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Infrastructure\Import\TerminalCsvFileParser;
use App\Client\Infrastructure\Import\TerminalRowValidator;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportTerminalsMessageHandlerTest extends TestCase
{
    private const CLIENT_ID    = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);
        return $path;
    }

    private function makeClient(): Client
    {
        return Client::createForTest(
            new ClientIdValueObject(self::CLIENT_ID),
            new ClientNameValueObject('Test Client'),
            new ClientRucValueObject('1712345675001'),
            'Address',
            ClientStatus::ACTIVE,
        );
    }

    private function makeRegisterHandler(
        ClientRepositoryInterface $clientRepo,
        TerminalRepositoryInterface $terminalRepo,
        IdGeneratorInterface $idGenerator,
    ): RegisterTerminalHandler {
        return new RegisterTerminalHandler(
            $clientRepo,
            $terminalRepo,
            $this->createMock(TransactionManagerInterface::class),
            $idGenerator,
        );
    }

    public function testDelegatesToHandler(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n1712345675001,ATM0000001,hyosung,dispenser,lobby";
        $path = $this->createTempCsv($csv);

        $client = $this->makeClient();

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->expects($this->once())->method('save');

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($client);
        $clientRepo->method('findById')->willReturn($client);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $innerHandler = new ImportTerminalsHandler(
            new TerminalCsvFileParser(new CsvFileParser()),
            new TerminalRowValidator(),
            $clientRepo,
            $this->makeRegisterHandler($clientRepo, $terminalRepo, $idGenerator),
        );

        $messageHandler = new ImportTerminalsMessageHandler($innerHandler);
        ($messageHandler)(new ImportTerminalsMessage(filePath: $path));
    }

    public function testDelegatesWithDryRun(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n1712345675001,ATM0000001,hyosung,dispenser,lobby";
        $path = $this->createTempCsv($csv);

        $client = $this->makeClient();

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->expects($this->never())->method('save');

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($client);
        $clientRepo->method('findById')->willReturn($client);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);

        $innerHandler = new ImportTerminalsHandler(
            new TerminalCsvFileParser(new CsvFileParser()),
            new TerminalRowValidator(),
            $clientRepo,
            $this->makeRegisterHandler($clientRepo, $terminalRepo, $idGenerator),
        );

        $messageHandler = new ImportTerminalsMessageHandler($innerHandler);
        ($messageHandler)(new ImportTerminalsMessage(filePath: $path, dryRun: true));
    }
}
