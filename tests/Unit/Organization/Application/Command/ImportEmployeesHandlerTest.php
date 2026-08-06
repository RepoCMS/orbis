<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Application\Command\ImportEmployees\ImportEmployeesCommand;
use App\Organization\Application\Command\ImportEmployees\ImportEmployeesHandler;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Infrastructure\Import\EmployeeCsvFileParser;
use App\Organization\Infrastructure\Import\EmployeeRowValidator;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportEmployeesHandlerTest extends TestCase
{
    private function createTempCsv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        $fp = fopen($path, 'w');
        if (!empty($rows)) {
            fputcsv($fp, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($fp, array_values($row));
            }
        }
        fclose($fp);

        return $path;
    }

    private function createRealCreateHandler(): CreateEmployeeHandler
    {
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('01JARZ3NDEKTSV4RRFFQ69G5FX');

        return new CreateEmployeeHandler(
            $this->createMock(EmployeeRepositoryInterface::class),
            $idGenerator,
            $this->createMock(DomainEventPublisherInterface::class),
        );
    }

    public function testImportsSuccessfully(): void
    {
        $path = $this->createTempCsv([
            ['identificacion' => '1710034065', 'nombres' => 'Juan Carlos', 'apellidos' => 'Perez Garcia', 'email' => 'juan@example.com', 'telefono' => '0998765432', 'fecha_ingreso' => '2024-01-15', 'perfil' => 'administrative'],
        ]);

        $parser = new EmployeeCsvFileParser(new CsvFileParser());
        $validator = new EmployeeRowValidator();

        $handler = new ImportEmployeesHandler($parser, $validator, $this->createRealCreateHandler());
        $result = ($handler)(new ImportEmployeesCommand(filePath: $path));

        unlink($path);

        self::assertSame(1, $result->imported, 'Errors: ' . implode('; ', $result->errorDetail));
        self::assertSame(0, $result->errors, 'Errors: ' . implode('; ', $result->errorDetail));
        self::assertSame(0, $result->skipped, 'Errors: ' . implode('; ', $result->errorDetail));
    }

    public function testDryRunDoesNotCreateEmployees(): void
    {
        $path = $this->createTempCsv([
            ['identificacion' => '1710034065', 'nombres' => 'Juan Carlos', 'apellidos' => 'Perez Garcia', 'email' => 'juan@example.com', 'telefono' => '0998765432', 'fecha_ingreso' => '2024-01-15', 'perfil' => 'administrative'],
        ]);

        $parser = new EmployeeCsvFileParser(new CsvFileParser());
        $validator = new EmployeeRowValidator();

        $handler = new ImportEmployeesHandler($parser, $validator, $this->createRealCreateHandler());
        $result = ($handler)(new ImportEmployeesCommand(filePath: $path, dryRun: true));

        unlink($path);

        self::assertSame(1, $result->imported, 'Errors: ' . implode('; ', $result->errorDetail));
        self::assertSame(0, $result->errors, 'Errors: ' . implode('; ', $result->errorDetail));
    }

    public function testReturnsErrorForEmptyFile(): void
    {
        $path = $this->createTempCsv([]);

        $parser = new EmployeeCsvFileParser(new CsvFileParser());
        $validator = new EmployeeRowValidator();

        $handler = new ImportEmployeesHandler(
            $parser,
            $validator,
            $this->createRealCreateHandler(),
        );

        $result = ($handler)(new ImportEmployeesCommand(filePath: $path));

        unlink($path);

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }

    public function testSkipsInvalidRows(): void
    {
        $path = $this->createTempCsv([
            ['identificacion' => '', 'nombres' => '', 'apellidos' => '', 'email' => '', 'telefono' => '', 'fecha_ingreso' => '', 'perfil' => ''],
            ['identificacion' => '1710034065', 'nombres' => 'Juan Carlos', 'apellidos' => 'Perez Garcia', 'email' => 'juan@example.com', 'telefono' => '0998765432', 'fecha_ingreso' => '2024-01-15', 'perfil' => 'administrative'],
        ]);

        $parser = new EmployeeCsvFileParser(new CsvFileParser());
        $validator = new EmployeeRowValidator();

        $handler = new ImportEmployeesHandler($parser, $validator, $this->createRealCreateHandler());
        $result = ($handler)(new ImportEmployeesCommand(filePath: $path));

        unlink($path);

        self::assertSame(1, $result->imported, 'Errors: ' . implode('; ', $result->errorDetail));
        self::assertSame(1, $result->errors, 'Errors: ' . implode('; ', $result->errorDetail));
        self::assertSame(0, $result->skipped, 'Errors: ' . implode('; ', $result->errorDetail));
    }
}
