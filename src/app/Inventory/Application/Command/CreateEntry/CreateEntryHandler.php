<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateEntry;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Entry;
use App\Inventory\Domain\Repositories\EntryRepositoryInterface;
use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreateEntryHandler
{
    public function __construct(
        private EntryRepositoryInterface $entryRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateEntryCommand $command): void
    {
        $entry = Entry::create(
            id:        new EntryIdValueObject($this->idGenerator->generate()),
            reference: $command->reference,
            notes:     $command->notes,
            createdBy: new UserIdValueObject($command->createdBy),
        );

        $this->entryRepository->save($entry);
    }
}
