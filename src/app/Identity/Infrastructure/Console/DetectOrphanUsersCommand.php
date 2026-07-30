<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\OrphanUserCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'identity:detect-orphan-users',
    description: 'Detecta usuarios que no están vinculados a ningún empleado o contacto.',
)]
final class DetectOrphanUsersCommand extends Command
{
    public function __construct(
        private readonly OrphanUserCheckerInterface $orphanUserChecker,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('csv', null, InputOption::VALUE_NONE, 'Exportar resultados en formato CSV');
        $this->addOption('auto-resolve', null, InputOption::VALUE_NONE, 'Soft-delete automático de usuarios huérfanos');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mostrar qué se haría sin ejecutar cambios');
        $this->addOption('notify-admin', null, InputOption::VALUE_NONE, 'Enviar notificación al administrador (log)');
        $this->addOption('auto-disable-after-days', null, InputOption::VALUE_REQUIRED, 'Deshabilitar usuarios huérfanos creados hace N días (usa con --dry-run para previsualizar)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allUsers = $this->userRepository->findAll();
        $orphans = [];
        $resolved = 0;

        foreach ($allUsers as $user) {
            if ($user->isDeleted()) {
                continue;
            }

            $userId = new UserIdValueObject((string) $user->id());
            if (!$this->orphanUserChecker->isBound($userId)) {
                $createdAt = $user->createdAt ?? null;
                $daysSinceCreation = 0;

                if ($createdAt !== null) {
                    $daysSinceCreation = (int) (new \DateTimeImmutable())->diff($createdAt)->days;
                }

                $orphans[] = [
                    'id' => (string) $user->id(),
                    'username' => (string) $user->username(),
                    'status' => $user->status()->value,
                    'createdAt' => $createdAt !== null ? $createdAt->format('Y-m-d') : 'N/A',
                    'daysSinceCreation' => $daysSinceCreation,
                ];
            }
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $autoResolve = (bool) $input->getOption('auto-resolve');
        $autoDisableAfterDays = $input->getOption('auto-disable-after-days');

        if ($autoDisableAfterDays !== null) {
            return $this->handleAutoDisable($orphans, (int) $autoDisableAfterDays, $dryRun, $output);
        }

        if ($input->getOption('notify-admin')) {
            $this->notifyAdmin($orphans, $output);
        }

        if ($autoResolve) {
            return $this->handleAutoResolve($orphans, $dryRun, $output);
        }

        if ($input->getOption('csv')) {
            return $this->exportCsv($orphans, $output);
        }

        if ($orphans === []) {
            $output->writeln('<info>No se encontraron usuarios huérfanos.</info>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<comment>Se encontraron %d usuario(s) huérfano(s) (sin Employee ni Contact vinculado):</comment>',
            count($orphans),
        ));
        $output->writeln('');

        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Username', 'Status', 'Creado', 'Días'])
            ->setRows(array_map(fn(array $o) => [
                $o['id'],
                $o['username'],
                $o['status'],
                $o['createdAt'],
                $o['daysSinceCreation'],
            ], $orphans));
        $table->render();

        return Command::FAILURE;
    }

    /**
     * @param array<int, array{id: string, username: string, status: string, createdAt: string, daysSinceCreation: int}> $orphans
     */
    private function handleAutoResolve(array $orphans, bool $dryRun, OutputInterface $output): int
    {
        $resolved = 0;

        foreach ($orphans as $o) {
            if ($dryRun) {
                $output->writeln(sprintf(
                    '[DRY-RUN] Se marcaría para soft-delete: %s (%s)',
                    $o['username'],
                    $o['id'],
                ));
                $resolved++;
                continue;
            }

            $user = $this->userRepository->findById(new UserIdValueObject($o['id']));
            if ($user !== null && !$user->isDeleted()) {
                $user->softDelete();
                $this->userRepository->save($user);
                $resolved++;
            }
        }

        $label = $dryRun ? 'previsualizados' : 'resueltos';
        $output->writeln(sprintf(
            '<info>%s %d de %d usuarios huérfanos (soft-delete).</info>',
            $dryRun ? 'Previsualizados' : 'Resueltos',
            $resolved,
            count($orphans),
        ));

        return $resolved > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<int, array{id: string, username: string, status: string, createdAt: string, daysSinceCreation: int}> $orphans
     */
    private function handleAutoDisable(array $orphans, int $days, bool $dryRun, OutputInterface $output): int
    {
        $cutoff = new \DateTimeImmutable(sprintf('-%d days', $days));
        $eligible = array_filter($orphans, fn(array $o) => $o['daysSinceCreation'] >= $days);
        $disabled = 0;

        foreach ($eligible as $o) {
            if ($dryRun) {
                $output->writeln(sprintf(
                    '[DRY-RUN] Se deshabilitaría: %s (%s) — creado hace %d días',
                    $o['username'],
                    $o['id'],
                    $o['daysSinceCreation'],
                ));
                $disabled++;
                continue;
            }

            $user = $this->userRepository->findById(new UserIdValueObject($o['id']));
            if ($user !== null && $user->isActive()) {
                $user->deactivate();
                $this->userRepository->save($user);
                $disabled++;
            }
        }

        $label = $dryRun ? 'previsualizados' : 'deshabilitados';
        $output->writeln(sprintf(
            '<info>%s %d de %d usuarios huérfanos (%d+ días).</info>',
            ucfirst($label),
            $disabled,
            count($eligible),
            $days,
        ));

        return $disabled > 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<int, array{id: string, username: string, status: string, createdAt: string, daysSinceCreation: int}> $orphans
     */
    private function notifyAdmin(array $orphans, OutputInterface $output): void
    {
        if ($orphans === []) {
            $output->writeln('<info>[NOTIFY] No hay huérfanos que notificar.</info>');
            return;
        }

        $output->writeln(sprintf(
            '<info>[NOTIFY] Se notificaría al administrador sobre %d usuario(s) huérfano(s):</info>',
            count($orphans),
        ));
        $output->writeln('');

        foreach ($orphans as $o) {
            $output->writeln(sprintf(
                '  • <comment>%s</comment> (%s) — %s, creado hace <info>%d</info> días',
                $o['username'],
                $o['id'],
                $o['status'],
                $o['daysSinceCreation'],
            ));
        }

        $output->writeln('');
        $output->writeln('<info>[NOTIFY] TODO: Integrar con Notification BC para envío real de notificación al administrador.</info>');
    }

    /**
     * @param array<int, array{id: string, username: string, status: string, createdAt: string, daysSinceCreation: int}> $orphans
     */
    private function exportCsv(array $orphans, OutputInterface $output): int
    {
        $output->writeln('id,username,status,created_at,days_since_creation');

        foreach ($orphans as $o) {
            $output->writeln(sprintf(
                '%s,%s,%s,%s,%d',
                $o['id'],
                $o['username'],
                $o['status'],
                $o['createdAt'],
                $o['daysSinceCreation'],
            ));
        }

        return $orphans === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
