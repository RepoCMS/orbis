<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'identity:cleanup-refresh-tokens',
    description: 'Elimina refresh tokens expirados o revocados.',
)]
final class CleanupExpiredRefreshTokensCommand extends Command
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleted = $this->refreshTokenRepository->deleteExpired(new DateTimeImmutable());

        $output->writeln(sprintf('Eliminados %d refresh tokens expirados/revocados.', $deleted));

        return Command::SUCCESS;
    }
}
