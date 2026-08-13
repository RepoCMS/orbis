<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Console;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Events\SpecialPeriodAutoProcessed;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Procesa las transiciones automáticas de períodos especiales de empleados.
 *
 * Ejecutar periódicamente via cron (recomendado: cada hora o cada día a medianoche):
 *   php bin/console org:process-special-periods
 *
 * Lógica:
 *  1. SCHEDULED → IN_PROGRESS cuando startedAt ≤ ahora
 *     - Si VACATION: cambia estado del empleado a ON_VACATION
 *     - Si STANDBY:  no cambia estado del empleado
 *
 *  2. IN_PROGRESS → COMPLETED cuando endedAt ≤ ahora
 *     - Si VACATION: cambia estado del empleado a ACTIVE
 *     - Si STANDBY:  no cambia estado del empleado
 */
#[AsCommand(
    name: 'org:process-special-periods',
    description: 'Procesa las transiciones automáticas de períodos especiales (vacaciones/standby).',
)]
final class ProcessEmployeeSpecialPeriodsCommand extends Command
{
    public function __construct(
        private readonly EmployeeSpecialPeriodRepositoryInterface $periodRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly DomainEventPublisherInterface $publisher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Procesando períodos especiales de empleados');

        $started   = 0;
        $completed = 0;
        $errors    = 0;

        // 1. SCHEDULED → IN_PROGRESS
        foreach ($this->periodRepository->findDueToStart() as $period) {
            try {
                $period->begin();
                $this->periodRepository->save($period);

                if ($period->type() === EmployeeSpecialPeriodType::VACATION) {
                    $employee = $this->employeeRepository->findById(
                        new EmployeeIdValueObject((string) $period->employeeId())
                    );

                    if ($employee !== null) {
                        $employee->startVacation();
                        $this->employeeRepository->save($employee);
                    }
                }

                $started++;
                $io->writeln(sprintf(
                    '  [INICIO] %s — empleado %s — tipo: %s',
                    $period->id(),
                    $period->employeeId(),
                    $period->type()->label(),
                ));

                // G-08: publish SpecialPeriodAutoProcessed for auto-processed start
                $this->publisher->publish(new SpecialPeriodAutoProcessed(
                    periodId: (string) $period->id(),
                    employeeId: (string) $period->employeeId(),
                    type: $period->type()->value,
                    transition: 'started',
                ));
            } catch (Throwable $e) {
                $errors++;
                $io->error(sprintf('Error al iniciar período %s: %s', $period->id(), $e->getMessage()));
            }
        }

        // 2. IN_PROGRESS → COMPLETED
        foreach ($this->periodRepository->findDueToEnd() as $period) {
            try {
                $period->complete();
                $this->periodRepository->save($period);

                if ($period->type() === EmployeeSpecialPeriodType::VACATION) {
                    $employee = $this->employeeRepository->findById(
                        new EmployeeIdValueObject((string) $period->employeeId())
                    );

                    if ($employee !== null) {
                        $employee->returnFromVacation();
                        $this->employeeRepository->save($employee);
                    }
                }

                $completed++;
                $io->writeln(sprintf(
                    '  [FIN]    %s — empleado %s — tipo: %s',
                    $period->id(),
                    $period->employeeId(),
                    $period->type()->label(),
                ));

                // G-08: publish SpecialPeriodAutoProcessed for auto-processed completion
                $this->publisher->publish(new SpecialPeriodAutoProcessed(
                    periodId: (string) $period->id(),
                    employeeId: (string) $period->employeeId(),
                    type: $period->type()->value,
                    transition: 'completed',
                ));
            } catch (Throwable $e) {
                $errors++;
                $io->error(sprintf('Error al completar período %s: %s', $period->id(), $e->getMessage()));
            }
        }

        $io->success(sprintf(
            'Procesados: %d iniciados, %d completados. Errores: %d.',
            $started,
            $completed,
            $errors,
        ));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
