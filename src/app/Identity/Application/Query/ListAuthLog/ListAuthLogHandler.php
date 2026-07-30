<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListAuthLog;

use App\Identity\Application\Query\Response\AuthLogResponse;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

final readonly class ListAuthLogHandler
{
    public function __construct(
        private AuthLogRepositoryInterface $authLogRepository,
    ) {
    }

    public function __invoke(ListAuthLogQuery $query): ListAuthLogResult
    {
        $userId = $query->userId !== null ? new UserIdValueObject($query->userId) : null;
        $dateFrom = $query->dateFrom !== null ? new DateTimeImmutable($query->dateFrom) : null;
        $dateTo = $query->dateTo !== null ? new DateTimeImmutable($query->dateTo) : null;

        $items = array_map(
            static fn($authLog) => AuthLogResponse::fromEntity($authLog),
            $this->authLogRepository->findByFilters($userId, $dateFrom, $dateTo, $query->page, $query->limit),
        );

        $total = $this->authLogRepository->countByFilters($userId, $dateFrom, $dateTo);

        return new ListAuthLogResult($items, $total, $query->page, $query->limit);
    }
}
