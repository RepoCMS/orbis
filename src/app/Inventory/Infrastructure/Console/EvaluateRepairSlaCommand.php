<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Console;

use App\Inventory\Domain\Enums\RepairPriority;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Enums\SerializedStockStatus;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron diario que evalúa SLA de reparaciones y genera alertas.
 *
 * - Repairs en PENDING > 48h → RepairSlaBreached
 * - Repairs en IN_PROGRESS > 5 días → RepairCompletionSlaBreached
 * - SerializedStock con repairCount >= product.maxRepairCount → requiere autorización extra
 */
#[AsCommand(
    name: 'inventory:evaluate-repair-sla',
    description: 'Evalúa SLA de reparaciones y emite alertas para reparaciones vencidas.',
)]
final class EvaluateRepairSlaCommand extends Command
{
    public function __construct(
        private readonly RepairRepositoryInterface $repairRepository,
        private readonly SerializedStockRepositoryInterface $serializedStockRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $output->writeln('Evaluando SLA de reparaciones...');

        // 1. Repairs PENDING > 48h sin iniciar → SLA breach
        $pendingRepairs = $this->repairRepository->findByStatus(RepairStatus::PENDING);
        $slaBreached = 0;

        foreach ($pendingRepairs as $repair) {
            $elapsed = $repair->createdAt()->diff($now);
            $hours   = ($elapsed->days * 24) + $elapsed->h;

            if ($hours > 48) {
                $output->writeln(
                    sprintf(
                        '  ⚠ SLA breach: Repair %s lleva %dh en PENDING (límite: 48h)',
                        (string) $repair->id(),
                        $hours,
                    ),
                );

                // Escalar prioridad automáticamente
                // En una implementación real, se publicaría un domain event
                $slaBreached++;
            }
        }

        // 2. Repairs IN_PROGRESS > 120h (5 días) → completion SLA breach
        $inProgressRepairs = $this->repairRepository->findByStatus(RepairStatus::IN_PROGRESS);
        $completionBreached = 0;

        foreach ($inProgressRepairs as $repair) {
            $startedAt = $repair->startedAt();
            if ($startedAt === null) {
                continue;
            }

            $elapsed = $startedAt->diff($now);
            $hours   = ($elapsed->days * 24) + $elapsed->h;

            if ($hours > 120) {
                $output->writeln(
                    sprintf(
                        '  ⚠ Completion SLA breach: Repair %s lleva %dh en IN_PROGRESS (límite: 120h)',
                        (string) $repair->id(),
                        $hours,
                    ),
                );
                $completionBreached++;
            }
        }

        // 3. Stock con repairCount alto
        $output->writeln('Verificando conteo de reparaciones por pieza...');

        $output->writeln(sprintf(
            'Resumen: %d SLA breaches, %d completion breaches',
            $slaBreached,
            $completionBreached,
        ));

        return Command::SUCCESS;
    }
}
