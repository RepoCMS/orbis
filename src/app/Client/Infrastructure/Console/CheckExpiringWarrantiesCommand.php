<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Console;

use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'client:check-expiring-warranties',
    description: 'Verifica garantías próximas a vencer (30 días) y emite alertas.',
)]
final class CheckExpiringWarrantiesCommand extends Command
{
    public function __construct(
        private TerminalWarrantyRepositoryInterface $warrantyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Garantías próximas a vencer');

        $allWarranties = $this->warrantyRepository->findAll();
        $expiringSoon = [];

        foreach ($allWarranties as $warranty) {
            if ($warranty->isAboutToExpire(30)) {
                $expiringSoon[] = $warranty;
            }
        }

        if (empty($expiringSoon)) {
            $io->success('No hay garantías próximas a vencer en los próximos 30 días.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('Se encontraron %d garantía(s) próxima(s) a vencer:', count($expiringSoon)));

        foreach ($expiringSoon as $w) {
            $io->writeln(sprintf(
                '  - Terminal: %s | Tipo: %s | Vence: %s',
                (string) $w->terminalId(),
                $w->type()->value,
                $w->endDate()->format('Y-m-d'),
            ));
        }

        return Command::SUCCESS;
    }
}
