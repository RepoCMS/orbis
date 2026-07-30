<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ImportClients;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportClientsMessageHandler
{
    public function __construct(
        private ImportClientsHandler $handler,
    ) {}

    public function __invoke(ImportClientsMessage $message): void
    {
        ($this->handler)(new ImportClientsCommand(
            filePath: $message->filePath,
            dryRun: $message->dryRun,
        ));
    }
}
