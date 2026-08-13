<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListContactsByClient;

use App\Client\Application\Query\Response\ContactResponse;
use App\Client\Domain\Repositories\ContactRepositoryInterface;

final readonly class ListContactsByClientHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    /**
     * @return ContactResponse[]
     */
    public function __invoke(ListContactsByClientQuery $query): array
    {
        return array_map(
            static fn ($contact) => ContactResponse::fromEntity($contact),
            $this->contactRepository->findByClientId($query->clientId),
        );
    }
}
