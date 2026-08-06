<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RegisterUser;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private PasswordHasherInterface $passwordHasher,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): string
    {
        $username = new UsernameValueObject($command->username);

        $this->ensureUsernameIsUnique($username);

        $roleId = new RoleIdValueObject($command->roleId);
        $this->ensureRoleExists($roleId);

        $rawPassword = new RawPasswordValueObject($command->rawPassword);
        $hashedPassword = $this->passwordHasher->hash($rawPassword);

        $id = new UserIdValueObject($this->idGenerator->generate());

        $user = User::register(
            $id,
            $username,
            $hashedPassword,
            $roleId,
        );

        $this->userRepository->save($user);

        $this->publisher->publish(new UserRegistered(
            userId:   (string) $id,
            username: $command->username,
            roleId:   $command->roleId,
        ));

        return (string) $id;
    }

    private function ensureUsernameIsUnique(UsernameValueObject $username): void
    {
        if ($this->userRepository->findByUsername($username) !== null) {
            throw UsernameAlreadyExistsException::withUsername((string) $username);
        }
    }

    private function ensureRoleExists(RoleIdValueObject $roleId): void
    {
        if ($this->roleRepository->findById($roleId) === null) {
            throw RoleNotFoundException::withId((string) $roleId);
        }
    }
}
