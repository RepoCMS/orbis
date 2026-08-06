<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContactsByClient;

use App\Client\Application\Query\Response\ContactResponse;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class FindContactsByClientHandler
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    /**
     * @return ContactResponse[]
     */
    public function __invoke(FindContactsByClientQuery $query): array
    {
        return array_map(
            static fn ($contact) => ContactResponse::fromEntity($contact),
            $this->contactRepository->findByClientId(new ClientIdValueObject($query->clientId)),
        );
    }
}
