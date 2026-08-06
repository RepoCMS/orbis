<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Persistence\Doctrine;

use App\Client\Domain\Criteria\ClientSearchCriteria;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return Client[]
     * - Listar todos los clientes activos, ordenados alfabéticamente por su nombre, asegurando que no se incluyan clientes que han sido eliminados (soft deleted).
     * - Implementar paginación para la lista de clientes si la cantidad de registros es grande, para mejorar el rendimiento y la experiencia del usuario.
     * - Permitir filtrar la lista de clientes por estado (ACTIVE/INACTIVE) o por parte del nombre, para facilitar la búsqueda de clientes específicos.
     * - Asegurar que las consultas a la base de datos estén optimizadas, utilizando índices adecuados en los campos de búsqueda como el nombre y el RUC.
     */
    public function search(ClientSearchCriteria $criteria): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Client::class, 'c')
            ->where('c.deletedAt IS NULL');

        if ($criteria->status !== null) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $criteria->status);
        }

        if ($criteria->name !== null) {
            $qb->andWhere('LOWER(c.name) LIKE :name')
                ->setParameter('name', '%' . strtolower($criteria->name) . '%');
        }

        if ($criteria->ruc !== null) {
            $qb->andWhere('c.ruc LIKE :ruc')
                ->setParameter('ruc', '%' . $criteria->ruc . '%');
        }

        return $qb
            ->orderBy('c.name', 'ASC')
            ->setFirstResult(($criteria->page - 1) * $criteria->limit)
            ->setMaxResults($criteria->limit)
            ->getQuery()
            ->getResult();
    }


    /**
     * @return Client|null
     * - Buscar un cliente por su ID o RUC, asegurando que no se devuelvan clientes que han sido eliminados (soft deleted).
     * - Si se encuentra un cliente pero está marcado como eliminado, se debe retornar null para mantener la consistencia de los datos.
     */
    public function findById(ClientIdValueObject $id): ?Client
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Client::class, 'c')
            ->where('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRuc(ClientRucValueObject $ruc): ?Client
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Client::class, 'c')
            ->where('c.ruc = :ruc')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('ruc', $ruc)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Client $client): void
    {
        $this->em->persist($client);
        $this->em->flush();
    }

    public function delete(ClientIdValueObject $id): void
    {
        $this->em->createQueryBuilder()
            ->update(Client::class, 'c')
            ->set('c.deletedAt', ':now')
            ->where('c.id = :id')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}
