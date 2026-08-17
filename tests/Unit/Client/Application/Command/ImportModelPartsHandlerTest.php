<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ImportModelParts\ImportModelPartsCommand;
use App\Client\Application\Command\ImportModelParts\ImportModelPartsHandler;
use App\Client\Domain\Repositories\ModelPartRepositoryInterface;
use App\Client\Infrastructure\Import\ModelPartCsvFileParser;
use App\Client\Infrastructure\Import\ModelPartRowValidator;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportModelPartsHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_parts_test_');
        file_put_contents($path, $content);

        return $path;
    }

    private function makeHandler(
        ?ModelPartRepositoryInterface $modelPartRepo = null,
        ?IdGeneratorInterface $idGenerator = null,
    ): ImportModelPartsHandler {
        return new ImportModelPartsHandler(
            new ModelPartCsvFileParser(new CsvFileParser()),
            new ModelPartRowValidator(),
            $modelPartRepo ?? $this->createMock(ModelPartRepositoryInterface::class),
            $idGenerator ?? $this->createMock(IdGeneratorInterface::class),
        );
    }

    public function testImportsPartsSuccessfully(): void
    {
        $csv = implode("\n", [
            'modelo,nombre_parte,numero_parte,nivel,padre',
            'Hyosung 8200ST,Dispenser,HP-001,1,',
            'Diebold 5500,主板,HP-002,2,HP-001',
        ]);
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(ModelPartRepositoryInterface::class);
        $repo->expects($this->exactly(2))->method('save');
        $repo->method('findByPartNumber')->willReturn(null);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $handler = $this->makeHandler($repo, $idGenerator);
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = implode("\n", [
            'modelo,nombre_parte,numero_parte,nivel,padre',
            'Hyosung 8200ST,Dispenser,HP-001,1,',
        ]);
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(ModelPartRepositoryInterface::class);
        $repo->method('findByPartNumber')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $handler = $this->makeHandler($repo);
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path, dryRun: true));

        self::assertSame(1, $result->imported);
        self::assertSame(0, $result->errors);
    }

    public function testSkipsDuplicatePartNumbers(): void
    {
        $csv = implode("\n", [
            'modelo,nombre_parte,numero_parte,nivel,padre',
            'Hyosung 8200ST,Dispenser,HP-001,1,',
        ]);
        $path = $this->createTempCsv($csv);

        // ModelPart is final, so we can't mock it directly.
        // Create a real instance via reflection using the factory method.
        $existingPart = \App\Client\Domain\Entities\ModelPart::register(
            id: new \App\Client\Domain\ValueObjects\ModelPartIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            model: 'Existing Model',
            partName: 'Existing Part',
            partNumber: 'HP-001',
            level: 1,
            parentPartNumber: null,
        );

        $repo = $this->createMock(ModelPartRepositoryInterface::class);
        $repo->method('findByPartNumber')->willReturn($existingPart);
        $repo->expects($this->never())->method('save');

        $handler = $this->makeHandler($repo);
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }

    public function testReturnsErrorsOnInvalidRows(): void
    {
        $csv = implode("\n", [
            'modelo,nombre_parte,numero_parte,nivel,padre',
            ',Parte sin modelo,HP-001,1,',
            'Hyosung,,HP-002,1,',
        ]);
        $path = $this->createTempCsv($csv);

        $repo = $this->createMock(ModelPartRepositoryInterface::class);
        $repo->method('findByPartNumber')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $handler = $this->makeHandler($repo);
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(2, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsEmptyOnEmptyFile(): void
    {
        $path = $this->createTempCsv("modelo,nombre_parte,numero_parte,nivel,padre\n");

        $handler = $this->makeHandler();
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path));

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
        $result = ($handler)(new ImportModelPartsCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
