<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportTerminals;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportTerminalsMessageHandler
{
    public function __construct(
        private ImportTerminalsHandler $handler,
    ) {
    }

    public function __invoke(ImportTerminalsMessage $message): void
    {
        ($this->handler)(new ImportTerminalsCommand(
            filePath: $message->filePath,
            dryRun: $message->dryRun,
        ));
    }
}
