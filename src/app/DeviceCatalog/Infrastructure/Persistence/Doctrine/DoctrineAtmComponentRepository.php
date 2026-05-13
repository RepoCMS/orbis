<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Infrastructure\Persistence\Doctrine;

use App\DeviceCatalog\Domain\AtmComponent;
use App\DeviceCatalog\Domain\AtmComponentId;
use App\DeviceCatalog\Domain\AtmComponentRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineAtmComponentRepository implements AtmComponentRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    public function findByParent(?AtmComponentId $parentId): array
    {
        if ($parentId === null) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT * FROM atm_components WHERE parent_id IS NULL AND active = 1 ORDER BY sort_order, name'
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT * FROM atm_components WHERE parent_id = ? AND active = 1 ORDER BY sort_order, name',
                [(string) $parentId]
            );
        }

        return array_map(static fn(array $r) => AtmComponent::reconstitute(
            $r['id'], $r['parent_id'], $r['name'], $r['aliases'],
            $r['level'], (int) $r['sort_order'], (bool) $r['active'],
        ), $rows);
    }

    public function findAllActive(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM atm_components WHERE active = 1 ORDER BY sort_order, name'
        );

        return array_map(static fn(array $r) => AtmComponent::reconstitute(
            $r['id'], $r['parent_id'], $r['name'], $r['aliases'],
            $r['level'], (int) $r['sort_order'], (bool) $r['active'],
        ), $rows);
    }

    public function findById(AtmComponentId $id): ?AtmComponent
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM atm_components WHERE id = ?',
            [(string) $id]
        );

        if (!$row) {
            return null;
        }

        return AtmComponent::reconstitute(
            $row['id'], $row['parent_id'], $row['name'], $row['aliases'],
            $row['level'], (int) $row['sort_order'], (bool) $row['active'],
        );
    }
}
