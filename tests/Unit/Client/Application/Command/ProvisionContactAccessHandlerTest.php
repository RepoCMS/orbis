<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\ProvisionContactAccess\ProvisionContactAccessCommand;
use App\Client\Application\Command\ProvisionContactAccess\ProvisionContactAccessHandler;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Events\ContactAccessProvisioned;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\AccountNotifierInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ProvisionContactAccessHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAY';
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAZ';

    public function testProvisionsAccessSuccessfully(): void
    {
        $contact = Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('john@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);
        $userRepo->method('findById')->willReturn(
            User::register(
                new UserIdValueObject(self::GENERATED_ID),
                new UsernameValueObject('john.doe'),
                new HashedPasswordValueObject('hashed'),
                new RoleIdValueObject(self::ROLE_ID),
            ),
        );
        $userRepo->expects($this->once())->method('save');

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(
            Role::create(
                new RoleIdValueObject(self::ROLE_ID),
                new RoleNameValueObject('Test Role'),
            ),
        );

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(
            new \App\Identity\Domain\ValueObjects\HashedPasswordValueObject('hashed'),
        );

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);

        $userTokenRepo = $this->createMock(UserTokenRepositoryInterface::class);

        $emailResolver = $this->createMock(UserEmailResolverInterface::class);
        $emailResolver->method('resolveEmail')->willReturn('john@test.com');

        $accountNotifier = $this->createMock(AccountNotifierInterface::class);
        $accountNotifier->expects($this->once())->method('sendActivationEmail');

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->exactly(2))->method('publish');

        $registerUser = new RegisterUserHandler(
            $userRepo,
            $roleRepo,
            $passwordHasher,
            $idGenerator,
            $publisher,
        );

        $linkUser = new \App\Client\Application\Command\LinkUserToContact\LinkUserToContactHandler(
            $contactRepo,
            $userRepo,
            $bindingChecker,
        );

        $generateToken = new GenerateActivationTokenHandler(
            $userRepo,
            $userTokenRepo,
            $accountNotifier,
            $emailResolver,
            $idGenerator,
        );

        $handler = new ProvisionContactAccessHandler(
            $registerUser,
            $linkUser,
            $generateToken,
            $publisher,
        );

        ($handler)(new ProvisionContactAccessCommand(
            contactId: self::CONTACT_ID,
            clientId: self::CLIENT_ID,
            username: 'john.doe',
            rawPassword: 'SecurePass1!',
            roleId: self::ROLE_ID,
        ));

        self::assertTrue(true);
    }

    public function testSucceedsWhenGenerateTokenFails(): void
    {
        $contact = Contact::create(
            id: new ContactIdValueObject(self::CONTACT_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            firstName: 'John',
            lastName: 'Doe',
            email: new ContactEmailValueObject('john@test.com'),
            phoneNumber: new ContactPhoneNumberValueObject('0999999999'),
            role: new ContactRoleValueObject('technical'),
        );

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);
        $userRepo->method('findById')->willReturn(
            User::register(
                new UserIdValueObject(self::GENERATED_ID),
                new UsernameValueObject('john.doe'),
                new HashedPasswordValueObject('hashed'),
                new RoleIdValueObject(self::ROLE_ID),
            ),
        );
        $userRepo->expects($this->once())->method('save');

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(
            Role::create(
                new RoleIdValueObject(self::ROLE_ID),
                new RoleNameValueObject('Test Role'),
            ),
        );

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(
            new \App\Identity\Domain\ValueObjects\HashedPasswordValueObject('hashed'),
        );

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $bindingChecker = $this->createMock(UserBindingCheckerInterface::class);

        $userTokenRepo = $this->createMock(UserTokenRepositoryInterface::class);

        $emailResolver = $this->createMock(UserEmailResolverInterface::class);
        $emailResolver->method('resolveEmail')->willReturn(null);

        $accountNotifier = $this->createMock(AccountNotifierInterface::class);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->exactly(2))->method('publish');

        $registerUser = new RegisterUserHandler(
            $userRepo,
            $roleRepo,
            $passwordHasher,
            $idGenerator,
            $publisher,
        );

        $linkUser = new \App\Client\Application\Command\LinkUserToContact\LinkUserToContactHandler(
            $contactRepo,
            $userRepo,
            $bindingChecker,
        );

        $generateToken = new GenerateActivationTokenHandler(
            $userRepo,
            $userTokenRepo,
            $accountNotifier,
            $emailResolver,
            $idGenerator,
        );

        $handler = new ProvisionContactAccessHandler(
            $registerUser,
            $linkUser,
            $generateToken,
            $publisher,
        );

        ($handler)(new ProvisionContactAccessCommand(
            contactId: self::CONTACT_ID,
            clientId: self::CLIENT_ID,
            username: 'john.doe',
            rawPassword: 'SecurePass1!',
            roleId: self::ROLE_ID,
        ));

        self::assertTrue(true);
    }
}
