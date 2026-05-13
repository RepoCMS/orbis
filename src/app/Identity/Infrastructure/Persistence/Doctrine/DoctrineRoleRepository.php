<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;

/**
 * Repositorio Doctrine para Role.
 *
 * La propiedad `permissionIds` del agregado NO está mapeada en el XML de Doctrine
 * (el dominio es puro). Por ello, este repositorio gestiona la tabla
 * `role_permissions` directamente vía DBAL e inyecta los datos en la entidad
 * usando ReflectionProperty después de cada carga.
 */
final class DoctrineRoleRepository implements RoleRepositoryInterface
{
    private readonly ReflectionProperty $permissionIdsRef;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
    ) {
        $this->permissionIdsRef = new ReflectionProperty(Role::class, 'permissionIds');
    }

    public function findById(RoleIdValueObject $id): ?Role
    {
        $role = $this->em->find(Role::class, $id);

        if ($role === null) {
            return null;
        }

        $this->injectPermissionIds($role);

        return $role;
    }

    public function findByName(RoleNameValueObject $name): ?Role
    {
        $role = $this->em->createQueryBuilder()
            ->select('r')
            ->from(Role::class, 'r')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();

        if ($role === null) {
            return null;
        }

        $this->injectPermissionIds($role);

        return $role;
    }

    public function findAll(): array
    {
        /** @var Role[] $roles */
        $roles = $this->em->createQueryBuilder()
            ->select('r')
            ->from(Role::class, 'r')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($roles as $role) {
            $this->injectPermissionIds($role);
        }

        return $roles;
    }

    public function save(Role $role): void
    {
        $this->em->persist($role);
        $this->em->flush();
        $this->syncPermissions($role);
    }

    public function delete(RoleIdValueObject $id): void
    {
        $roleId = (string) $id;
        $this->connection->delete('role_permissions', ['role_id' => $roleId]);

        $role = $this->em->find(Role::class, $id);

        if ($role !== null) {
            $this->em->remove($role);
            $this->em->flush();
        }
    }

    /**
     * Carga los permission_ids desde role_permissions e inyecta el array indexado
     * en la propiedad privada del agregado mediante reflection.
     */
    private function injectPermissionIds(Role $role): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT permission_id FROM role_permissions WHERE role_id = ?',
            [(string) $role->id()],
        );

        $indexed = [];
        foreach ($rows as $row) {
            $pid = new PermissionIdValueObject($row['permission_id']);
            $indexed[(string) $pid] = $pid;
        }

        $this->permissionIdsRef->setValue($role, $indexed);
    }

    /**
     * Sincroniza role_permissions: elimina todo lo antiguo e inserta el estado actual.
     * Estrategia delete-insert para simplicidad y correctitud.
     */
    private function syncPermissions(Role $role): void
    {
        $roleId = (string) $role->id();

        $this->connection->delete('role_permissions', ['role_id' => $roleId]);

        foreach ($role->permissionIds() as $permissionId) {
            $this->connection->insert('role_permissions', [
                'role_id'       => $roleId,
                'permission_id' => (string) $permissionId,
            ]);
        }
    }
}
