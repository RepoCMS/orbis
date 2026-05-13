<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\Site;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;

interface SiteRepositoryInterface
{
    public function findById(SiteIdValueObject $id): ?Site;

    /** @return Site[] */
    public function findByClientId(ClientIdValueObject $clientId): array;

    /** @return Site[] */
    public function findAll(): array;

    public function save(Site $site): void;
}
