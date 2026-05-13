<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Entities\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Adapter entre el User de dominio y el UserInterface de Symfony Security.
 */
final class SymfonySecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param string[] $permissionKeys
     */
    public function __construct(
        private readonly User $domainUser,
        private readonly array $permissionKeys = [],
    ) {
    }

    public function getDomainUser(): User
    {
        return $this->domainUser;
    }

    /**
     * @return string[]
     */
    public function getPermissionKeys(): array
    {
        return $this->permissionKeys;
    }

    public function hasPermission(string $permissionKey): bool
    {
        return in_array($permissionKey, $this->permissionKeys, true);
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->domainUser->username();
    }

    /** All authenticated users carry ROLE_USER; domain roles handle fine-grained authorization. */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return (string) $this->domainUser->password();
    }

    public function eraseCredentials(): void
    {
        // password is already hashed — nothing to erase
    }
}
