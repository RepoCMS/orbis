<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Limpia archivos de reportes temporales con más de 90 días de antigüedad.
 *
 * Se ejecuta vía cron:
 *   0 3 * * 0 php /path/to/bin/console app:reports:cleanup
 */
#[AsCommand(
    name: 'app:reports:cleanup',
    description: 'Elimina archivos de reportes temporales con más de 90 días.',
)]
final class CleanupReportsCommand extends Command
{
    private const RETENTION_DAYS = 90;

    public function __construct(
        private readonly string $reportsDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Limpieza de reportes temporales');

        if (!is_dir($this->reportsDir)) {
            $io->warning(sprintf('El directorio "%s" no existe.', $this->reportsDir));

            return Command::SUCCESS;
        }

        $cutoff = new \DateTimeImmutable(-self::RETENTION_DAYS . ' days');
        $deleted = 0;
        $errors = 0;

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->reportsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        ) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            // Only clean up report files (xlsx, pdf, csv)
            $extension = $file->getExtension();
            if (!\in_array($extension, ['xlsx', 'pdf', 'csv', 'html'], true)) {
                continue;
            }

            $mtime = \DateTimeImmutable::createFromFormat('U', (string) $file->getMTime());
            if ($mtime === false) {
                continue;
            }

            if ($mtime < $cutoff) {
                try {
                    \unlink($file->getRealPath());
                    $io->writeln(sprintf('  <info>✓ Eliminado</info> %s', $file->getFilename()));
                    $deleted++;
                } catch (\Throwable $e) {
                    $io->writeln(sprintf('  <error>✗ Error</error> %s: %s', $file->getFilename(), $e->getMessage()));
                    $errors++;
                }
            }
        }

        $io->newLine();
        if ($deleted > 0 || $errors > 0) {
            $io->success(sprintf('%d archivos eliminados, %d errores.', $deleted, $errors));
        } else {
            $io->info('No se encontraron archivos antiguos para limpiar.');
        }

        return Command::SUCCESS;
    }
}
