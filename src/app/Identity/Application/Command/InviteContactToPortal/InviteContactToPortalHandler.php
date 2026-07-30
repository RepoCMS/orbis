<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\InviteContactToPortal;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenCommand;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Exception\ContactAlreadyHasAccessException;
use App\Identity\Application\Exception\ContactNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\Events\ContactLinked;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\ContactFinderInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class InviteContactToPortalHandler
{
    private const CLIENT_PORTAL_ROLE_SLUG = 'client_portal';

    public function __construct(
        private ContactFinderInterface $contactFinder,
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private PasswordHasherInterface $passwordHasher,
        private IdGeneratorInterface $idGenerator,
        private AuthLogRepositoryInterface $authLogRepository,
        private GenerateActivationTokenHandler $generateActivationToken,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(InviteContactToPortalCommand $command): string
    {
        $contact = $this->contactFinder->findById($command->contactId);

        if ($contact === null) {
            throw ContactNotFoundException::withId($command->contactId);
        }

        $this->ensureContactNotLinked($command->contactId);

        $username = $this->generateUsername($contact['firstName'], $contact['lastName']);

        $role = $this->roleRepository->findBySlug(new RoleSlugValueObject(self::CLIENT_PORTAL_ROLE_SLUG));

        if ($role === null) {
            throw RoleNotFoundException::withSlug(self::CLIENT_PORTAL_ROLE_SLUG);
        }

        $tempPassword = $this->generateTempPassword();
        $hashedPassword = $this->passwordHasher->hash(new RawPasswordValueObject($tempPassword));

        $id = new UserIdValueObject($this->idGenerator->generate());

        $user = User::register($id, $username, $hashedPassword, $role->id());

        $this->userRepository->save($user);

        // Vincular el User al Contact (guardar userId en Contact)
        $contactEntity = $this->contactRepository->findById(new ContactIdValueObject($command->contactId));
        if ($contactEntity !== null) {
            $contactEntity->linkToUser((string) $id);
            $this->contactRepository->save($contactEntity);
        }

        $this->publisher->publish(new ContactLinked(
            userId: (string) $id,
            contactId: $command->contactId,
            clientId: $contact['clientId'],
        ));

        try {
            ($this->generateActivationToken)(new GenerateActivationTokenCommand(userId: (string) $id));
        } catch (\Throwable) {
            // No crítico: el token puede regenerarse manualmente
        }

        $this->authLogRepository->append(AuthLog::record(
            AuthLogIdValueObject::fromString($this->idGenerator->generate()),
            $id,
            AuthEvent::USER_CREATED,
            '0.0.0.0',
            'admin',
        ));

        return (string) $id;
    }

    private function ensureContactNotLinked(string $contactId): void
    {
        $contact = $this->contactRepository->findById(new ContactIdValueObject($contactId));

        if ($contact !== null && $contact->userId() !== null) {
            throw ContactAlreadyHasAccessException::withId($contactId);
        }
    }

    private function generateUsername(string $firstName, string $lastName): UsernameValueObject
    {
        $base = mb_strtolower(
            iconv('UTF-8', 'ASCII//TRANSLIT', $firstName . '.' . $lastName)
        );
        $base = preg_replace('/[^a-z0-9.]/', '', $base);
        $base = mb_substr($base, 0, 15);

        $username = $base;
        $suffix = 1;

        while ($this->userRepository->findByUsername(new UsernameValueObject($username)) !== null) {
            $suffixStr = (string) $suffix;
            $truncated = mb_substr($base, 0, 15 - 1 - strlen($suffixStr));
            $username = $truncated . '.' . $suffixStr;
            $suffix++;
        }

        return new UsernameValueObject($username);
    }

    private function generateTempPassword(): string
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $special = '@#$%&*!';

        return $upper[random_int(0, 25)]
            . $lower[random_int(0, 25)]
            . $digits[random_int(0, 9)]
            . $special[random_int(0, 6)]
            . bin2hex(random_bytes(6));
    }
}
