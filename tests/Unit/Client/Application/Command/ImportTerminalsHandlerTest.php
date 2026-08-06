<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportTerminals\ImportTerminalsCommand;
use App\Client\Application\Command\ImportTerminals\ImportTerminalsHandler;
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

final class ImportTerminalsHandlerTest extends TestCase
{
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
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

    private function makeHandler(
        ?ClientRepositoryInterface $clientRepo = null,
        ?RegisterTerminalHandler $registerHandler = null,
    ): ImportTerminalsHandler {
        return new ImportTerminalsHandler(
            new TerminalCsvFileParser(new CsvFileParser()),
            new TerminalRowValidator(),
            $clientRepo ?? $this->createMock(ClientRepositoryInterface::class),
            $registerHandler ?? $this->createRegisterHandler(),
        );
    }

    private function createRegisterHandler(
        ?TerminalRepositoryInterface $terminalRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
        ?TransactionManagerInterface $transactionManager = null,
    ): RegisterTerminalHandler {
        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findById')->willReturn($this->makeClient());

        return new RegisterTerminalHandler(
            $clientRepo,
            $terminalRepo ?? $this->createMock(TerminalRepositoryInterface::class),
            $transactionManager ?? $this->createStub(TransactionManagerInterface::class),
            $idGenerator ?? $this->createIdGenerator(),
        );
    }

    private function createIdGenerator(): IdGeneratorInterface
    {
        $idGen = $this->createMock(IdGeneratorInterface::class);
        $idGen->method('generate')->willReturn(self::GENERATED_ID);

        return $idGen;
    }

    public function testImportsTerminalsSuccessfully(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n1712345675001,ATM0000001,hyosung,dispenser,lobby\n1712345675001,ATM0000002,diebold,recycler,ttw";
        $path = $this->createTempCsv($csv);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->expects($this->exactly(2))->method('save');

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($this->makeClient());

        $handler = $this->makeHandler(
            $clientRepo,
            $this->createRegisterHandler($terminalRepo),
        );

        $result = ($handler)(new ImportTerminalsCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotRegisterTerminals(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n1712345675001,ATM0000001,hyosung,dispenser,lobby";
        $path = $this->createTempCsv($csv);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->expects($this->never())->method('save');

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn($this->makeClient());

        $handler = $this->makeHandler(
            $clientRepo,
            $this->createRegisterHandler($terminalRepo),
        );

        $result = ($handler)(new ImportTerminalsCommand(filePath: $path, dryRun: true));

        self::assertSame(1, $result->imported);
    }

    public function testSkipsWhenClientNotFound(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n0100000009001,ATM0000001,hyosung,dispenser,lobby";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->method('findByRuc')->willReturn(null);

        $handler = $this->makeHandler($clientRepo);
        $result = ($handler)(new ImportTerminalsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n,invalid,,,,\n12345,ATM-002,wrong,bad,unknown";
        $path = $this->createTempCsv($csv);

        $clientRepo = $this->createMock(ClientRepositoryInterface::class);
        $clientRepo->expects($this->never())->method('findByRuc');

        $handler = $this->makeHandler($clientRepo);
        $result = ($handler)(new ImportTerminalsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(2, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $csv = "ruc,serial,vendor,funcionality,type\n";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportTerminalsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportTerminalsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
