<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Carga el User de dominio desde la base de datos para Symfony Security.
 *
 * @implements UserProviderInterface<SymfonySecurityUser>
 */
final class SymfonyUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $username = new UsernameValueObject($identifier);
        } catch (\Throwable) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        $user = $this->userRepository->findByUsername($username);

        if ($user === null || $user->isDeleted()) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        if ($user->status() === UserStatus::UNVERIFIED) {
            throw new CustomUserMessageAccountStatusException(
                'Tu cuenta aún no ha sido verificada. Revisa tu correo electrónico.'
            );
        }

        if ($user->status() === UserStatus::INACTIVE) {
            throw new CustomUserMessageAccountStatusException(
                'Tu cuenta está inactiva. Contacta al administrador.'
            );
        }

        return new SymfonySecurityUser($user, $this->loadPermissionKeys($user->roleId()));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === SymfonySecurityUser::class;
    }

    /**
     * @return string[]
     */
    private function loadPermissionKeys(RoleIdValueObject $roleId): array
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            return [];
        }

        return array_map(
            static fn(Permission $permission): string => (string) $permission->key(),
            $this->permissionRepository->findByIds($role->permissionIds()),
        );
    }
}
