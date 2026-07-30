<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ImportOperatingExpenses;

use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemCommand;
use App\Expense\Application\Command\AddExpenseItem\AddExpenseItemHandler;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseCommand;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseHandler;
use App\Expense\Infrastructure\Import\OperatingExpenseCsvFileParser;
use App\Expense\Infrastructure\Import\OperatingExpenseRowValidator;
use App\Shared\Application\Import\ImportResult;

final readonly class ImportOperatingExpensesHandler
{
    public function __construct(
        private OperatingExpenseCsvFileParser $parser,
        private OperatingExpenseRowValidator $rowValidator,
        private CreateOperatingExpenseHandler $createExpenseHandler,
        private AddExpenseItemHandler $addItemHandler,
    ) {}

    public function __invoke(ImportOperatingExpensesCommand $command): ImportResult
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

        $expenseIds = [];

        foreach ($rows as $idx => $row) {
            $rowNumber = $idx + 2;

            $validationErrors = $this->rowValidator->validate($row, $rowNumber);
            if ($validationErrors !== []) {
                $errors++;
                $errorDetail = array_merge($errorDetail, $validationErrors);
                continue;
            }

            $employeeId = trim($row['empleado_id']);
            $tipo = trim($row['tipo']);
            $monto = (float) trim($row['monto']);
            $fecha = trim($row['fecha']);
            $evidencia = trim($row['ruta_evidencia'] ?? '');

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                if (!isset($expenseIds[$employeeId])) {
                    $expenseId = ($this->createExpenseHandler)(new CreateOperatingExpenseCommand(
                        employeeId: $employeeId,
                        reason: 'Importación masiva de gastos',
                        startDate: $fecha,
                        endDate: $fecha,
                    ));
                    $expenseIds[$employeeId] = $expenseId;
                }

                $expenseId = $expenseIds[$employeeId];

                $notes = $evidencia !== '' ? sprintf('Evidencia: %s', $evidencia) : null;
                ($this->addItemHandler)(new AddExpenseItemCommand(
                    expenseId: $expenseId,
                    type: $tipo,
                    amount: $monto,
                    notes: $notes,
                ));

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al importar gasto: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
