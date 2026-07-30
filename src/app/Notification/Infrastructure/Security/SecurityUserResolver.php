<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Security;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Notification\Application\Service\SecurityUserResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class SecurityUserResolver implements SecurityUserResolverInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function getUserId(): ?string
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof SymfonySecurityUser) {
            return null;
        }

        return (string) $user->getDomainUser()->id();
    }
}
