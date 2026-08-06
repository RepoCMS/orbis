<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportTerminalModels\ImportTerminalModelsCommand;
use App\Client\Application\Command\ImportTerminalModels\ImportTerminalModelsHandler;
use App\Client\Domain\Repositories\TerminalModelRepositoryInterface;
use App\Client\Infrastructure\Import\TerminalModelCsvFileParser;
use App\Client\Infrastructure\Import\TerminalModelRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportTerminalModelsHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);

        return $path;
    }

    private function makeHandler(
        ?TerminalModelRepositoryInterface $terminalModelRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
    ): ImportTerminalModelsHandler {
        return new ImportTerminalModelsHandler(
            new TerminalModelCsvFileParser(new CsvFileParser()),
            new TerminalModelRowValidator(),
            $terminalModelRepo ?? $this->createMock(TerminalModelRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
        );
    }

    public function testImportsModelsSuccessfully(): void
    {
        $csv = "name,description\nHyosung 8200ST,Modelo estándar\nDiebold 5500,Modelo económico";
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(TerminalModelRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = $this->makeHandler($repo, $idGenerator);
        $result = ($handler)(new ImportTerminalModelsCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "name,description\nHyosung 8200ST,Modelo estándar\nDiebold 5500,Modelo económico";
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(TerminalModelRepositoryInterface::class);
        $repo->expects($this->never())->method('save');

        $handler = $this->makeHandler($repo);
        $result = ($handler)(new ImportTerminalModelsCommand(filePath: $path, dryRun: true));

        self::assertSame(2, $result->imported);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = "name,description\n,Modelo sin nombre\n";
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(TerminalModelRepositoryInterface::class);
        $repo->expects($this->never())->method('save');

        $handler = $this->makeHandler($repo);
        $result = ($handler)(new ImportTerminalModelsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $path = $this->createTempCsv("name,description\n");

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportTerminalModelsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportTerminalModelsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
