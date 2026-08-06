<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ImportEmployees;

use App\Organization\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Application\Exception\EmployeeDuplicateEmailException;
use App\Organization\Application\Exception\EmployeeDuplicateIdentificationException;
use App\Organization\Infrastructure\Import\EmployeeCsvFileParser;
use App\Organization\Infrastructure\Import\EmployeeRowValidator;
use App\Shared\Application\Import\ImportResult;

final readonly class ImportEmployeesHandler
{
    public function __construct(
        private EmployeeCsvFileParser $parser,
        private EmployeeRowValidator $rowValidator,
        private CreateEmployeeHandler $createEmployee,
    ) {
    }

    public function __invoke(ImportEmployeesCommand $command): ImportResult
    {
        $rows = $this->parser->parse($command->filePath);

        if (empty($rows)) {
            return new ImportResult(0, 0, 0, ['Archivo vacÃ­o o sin datos']);
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

            $profile = trim($row['perfil'] ?? '');
            if ($profile === '') {
                $profile = 'administrative';
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                ($this->createEmployee)(new CreateEmployeeCommand(
                    identificationNumber: trim($row['identificacion']),
                    firstName: trim($row['nombres']),
                    lastName: trim($row['apellidos']),
                    email: trim($row['email']),
                    phoneNumber: trim($row['telefono']),
                    hiredAt: trim($row['fecha_ingreso']),
                    profile: $profile,
                ));

                $imported++;
            } catch (EmployeeDuplicateIdentificationException $e) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: %s', $rowNumber, $e->getMessage());
            } catch (EmployeeDuplicateEmailException $e) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: %s', $rowNumber, $e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error de validación: %s', $rowNumber, $e->getMessage());
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error inesperado: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
