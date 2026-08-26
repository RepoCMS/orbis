<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Command\InviteContactToPortal\InviteContactToPortalCommand;
use App\Identity\Application\Command\InviteContactToPortal\InviteContactToPortalHandler;
use App\Identity\Application\Exception\ContactNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\AccountNotifierInterface;
use App\Identity\Domain\Service\ContactFinderInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class InviteContactToPortalHandlerTest extends TestCase
{
    private const CONTACT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    private function createContact(bool $linked = false): Contact
    {
        return Contact::create(
            new ContactIdValueObject(self::CONTACT_ID),
            new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA3'),
            'Juan',
            'Perez',
            new ContactEmailValueObject('juan@example.com'),
            new ContactPhoneNumberValueObject('0999999999'),
            new ContactRoleValueObject('technical'),
            $linked ? new \App\Identity\Domain\ValueObjects\UserIdValueObject(self::USER_ID) : null,
        );
    }

    public function testInvitesContactSuccessfully(): void
    {
        $contactFinder = $this->createMock(ContactFinderInterface::class);
        $contactFinder->method('findById')->willReturn([
            'id' => self::CONTACT_ID,
            'clientId' => '01ARZ3NDEKTSV4RRFFQ69G5FA3',
            'firstName' => 'Juan',
            'lastName' => 'Perez',
            'email' => 'juan@example.com',
        ]);

        $contact = $this->createContact();

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($contact);
        $contactRepo->expects($this->once())->method('save');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);
        $userRepo->expects($this->once())->method('save');

        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Client Portal'),
            new RoleSlugValueObject('client-portal'),
        );
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findBySlug')->willReturn($role);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn(new HashedPasswordValueObject('$2y$12$hash'));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::USER_ID);

        $authLogRepo = $this->createMock(AuthLogRepositoryInterface::class);
        $authLogRepo->expects($this->once())->method('append');

        $activationHandler = new GenerateActivationTokenHandler(
            $userRepo,
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(AccountNotifierInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $idGenerator,
        );

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $handler = new InviteContactToPortalHandler(
            $contactFinder,
            $contactRepo,
            $userRepo,
            $roleRepo,
            $hasher,
            $idGenerator,
            $authLogRepo,
            $activationHandler,
            $publisher,
        );
        $result = ($handler)(new InviteContactToPortalCommand(self::CONTACT_ID));

        self::assertSame(self::USER_ID, $result);
    }

    public function testThrowsWhenContactNotFound(): void
    {
        $contactFinder = $this->createMock(ContactFinderInterface::class);
        $contactFinder->method('findById')->willReturn(null);

        $activationHandler = new GenerateActivationTokenHandler(
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(AccountNotifierInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $handler = new InviteContactToPortalHandler(
            $contactFinder,
            $this->createMock(ContactRepositoryInterface::class),
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(RoleRepositoryInterface::class),
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
            $activationHandler,
            $this->createMock(DomainEventPublisherInterface::class),
        );
        $this->expectException(ContactNotFoundException::class);
        ($handler)(new InviteContactToPortalCommand(self::CONTACT_ID));
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $contactFinder = $this->createMock(ContactFinderInterface::class);
        $contactFinder->method('findById')->willReturn([
            'id' => self::CONTACT_ID,
            'clientId' => '01ARZ3NDEKTSV4RRFFQ69G5FA3',
            'firstName' => 'Juan',
            'lastName' => 'Perez',
            'email' => 'juan@example.com',
        ]);

        $contactRepo = $this->createMock(ContactRepositoryInterface::class);
        $contactRepo->method('findById')->willReturn($this->createContact());

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findBySlug')->willReturn(null);

        $activationHandler = new GenerateActivationTokenHandler(
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(AccountNotifierInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );

        $handler = new InviteContactToPortalHandler(
            $contactFinder,
            $contactRepo,
            $this->createMock(UserRepositoryInterface::class),
            $roleRepo,
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
            $activationHandler,
            $this->createMock(DomainEventPublisherInterface::class),
        );
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new InviteContactToPortalCommand(self::CONTACT_ID));
    }
}
