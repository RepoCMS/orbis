<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTerminalRepository implements TerminalRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function findById(TerminalIdValueObject $id): ?Terminal
    {
        $terminal = $this->em->find(Terminal::class, $id);

        if ($terminal !== null && $terminal->isDeleted()) {
            return null;
        }

        return $terminal;
    }

    public function findByClientId(ClientIdValueObject $clientId): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(Terminal::class, 't')
            ->where('t.clientId = :clientId')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getResult();
    }

    public function save(Terminal $terminal): void
    {
        $this->em->persist($terminal);
        $this->em->flush();
    }

    public function delete(TerminalIdValueObject $id): void
    {
        $terminal = $this->em->find(Terminal::class, $id);

        if ($terminal !== null) {
            $terminal->softDelete();
            $this->save($terminal);
        }
    }
}
