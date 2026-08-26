<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testRegistersUserSuccessfully(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);
        $userRepo->expects($this->once())->method('save');

        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::USER_ID);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn(new HashedPasswordValueObject('$2y$12$hashedpassword'));

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(UserRegistered::class));

        $handler = new RegisterUserHandler($userRepo, $roleRepo, $hasher, $idGenerator, $publisher);
        $result = ($handler)(new RegisterUserCommand(
            username: 'juan.perez',
            rawPassword: 'SecurePass1!',
            roleId: self::ROLE_ID,
        ));

        self::assertSame(self::USER_ID, $result);
    }

    public function testThrowsWhenUsernameExists(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(
            \App\Identity\Domain\Entities\User::register(
                new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::USER_ID),
                new \App\Identity\Domain\ValueObjects\UsernameValueObject('existing'),
                new HashedPasswordValueObject('$2y$12$hash'),
                new RoleIdValueObject(self::ROLE_ID),
            )
        );

        $handler = new RegisterUserHandler(
            $userRepo,
            $this->createMock(RoleRepositoryInterface::class),
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(UsernameAlreadyExistsException::class);
        ($handler)(new RegisterUserCommand('juan.perez', 'SecurePass1!', self::ROLE_ID));
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(null);

        $handler = new RegisterUserHandler(
            $userRepo,
            $roleRepo,
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(RoleNotFoundException::class);
        ($handler)(new RegisterUserCommand('juan.perez', 'SecurePass1!', self::ROLE_ID));
    }
}
