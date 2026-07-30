<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Console;

use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron que verifica usos de emergencia >48h sin conciliar.
 *
 * Verifica movimientos PENDING que superen las 48 horas desde su creación.
 * Estos representan usos de emergencia (SUSPECT/PENDING_REVIEW) que deben
 * reconciliarse dentro del plazo establecido.
 */
#[AsCommand(
    name: 'inventory:evaluate-emergency-reconciliation',
    description: 'Verifica usos de emergencia >48h sin conciliar y emite alertas.',
)]
final class EvaluateEmergencyReconciliationCommand extends Command
{
    public function __construct(
        private readonly MovementRepositoryInterface $movementRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now     = new \DateTimeImmutable();
        $overdue = 0;
        $total   = 0;

        $output->writeln('Verificando usos de emergencia pendientes de reconciliación...');

        $movements = $this->movementRepository->findByStatus(MovementStatus::PENDING);

        foreach ($movements as $movement) {
            $total++;
            $createdAt = $movement->createdAt();
            $elapsed   = $createdAt->diff($now);
            $hours     = ($elapsed->days * 24) + $elapsed->h;

            $output->writeln(sprintf(
                '  Movement %s: %dh transcurridos (límite: 48h)',
                (string) $movement->id(),
                $hours,
            ));

            if ($hours > 48) {
                $output->writeln(sprintf(
                    '  ⚠ EMERGENCY RECONCILIATION OVERDUE: Movement %s superó las 48h.',
                    (string) $movement->id(),
                ));
                $overdue++;
            }
        }

        $output->writeln(sprintf(
            'Resumen: %d movimientos PENDING revisados, %d vencidos (>48h).',
            $total,
            $overdue,
        ));

        return Command::SUCCESS;
    }
}
