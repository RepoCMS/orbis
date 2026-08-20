<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure\Service;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Notification\Infrastructure\Service\UserContactResolver;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UserContactResolverTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private UserContactResolver $resolver;
    private EmployeeRepositoryInterface $employeeRepository;
    private ContactRepositoryInterface $contactRepository;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->employeeRepository = $this->createMock(EmployeeRepositoryInterface::class);
        $this->contactRepository = $this->createMock(ContactRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->resolver = new UserContactResolver(
            $this->employeeRepository,
            $this->contactRepository,
            $this->userRepository,
        );
    }

    public function testResolveReturnsEmptyForWrongRecipientType(): void
    {
        $result = $this->resolver->resolve('employee', self::USER_ID);

        self::assertSame(['email' => null, 'phone' => null, 'name' => null], $result);
    }

    public function testResolveFindsEmployeeFirst(): void
    {
        $employee = $this->createEmployee('emp@example.com', '0991234567', 'Johnathan Smith');
        $userIdVO = UserIdValueObject::fromString(self::USER_ID);
        $this->employeeRepository->method('findByUserId')
            ->willReturnCallback(function (UserIdValueObject $id) use ($employee, $userIdVO) {
                return $id->equals($userIdVO) ? $employee : null;
            });
        $this->contactRepository->method('findByUserId')->willReturn(null);

        $result = $this->resolver->resolve('user', self::USER_ID);

        self::assertSame([
            'email' => 'emp@example.com',
            'phone' => '0991234567',
            'name' => 'Johnathan Smith',
        ], $result);
    }

    public function testResolveFallsBackToContactWhenNoEmployee(): void
    {
        $contact = $this->createContact('contact@example.com', '0997654321', 'Janet Roberts');
        $userIdVO = UserIdValueObject::fromString(self::USER_ID);
        $this->employeeRepository->method('findByUserId')
            ->willReturn(null);
        $this->contactRepository->method('findByUserId')
            ->willReturnCallback(function (UserIdValueObject $id) use ($contact, $userIdVO) {
                return $id->equals($userIdVO) ? $contact : null;
            });
        $this->userRepository->method('findById')->willReturn(null);

        $result = $this->resolver->resolve('user', self::USER_ID);

        self::assertSame([
            'email' => 'contact@example.com',
            'phone' => '0997654321',
            'name' => 'Janet Roberts',
        ], $result);
    }

    public function testResolveFallsBackToUserWhenNoEmployeeOrContact(): void
    {
        $user = $this->createUser('testuser');
        $userIdVO = UserIdValueObject::fromString(self::USER_ID);
        $this->employeeRepository->method('findByUserId')
            ->willReturn(null);
        $this->contactRepository->method('findByUserId')
            ->willReturn(null);
        $this->userRepository->method('findById')
            ->willReturnCallback(function (UserIdValueObject $id) use ($user, $userIdVO) {
                return $id->equals($userIdVO) ? $user : null;
            });

        $result = $this->resolver->resolve('user', self::USER_ID);

        self::assertSame([
            'email' => 'testuser',
            'phone' => null,
            'name' => 'testuser',
        ], $result);
    }

    public function testResolveReturnsEmptyWhenUserNotFound(): void
    {
        $this->employeeRepository->method('findByUserId')->willReturn(null);
        $this->contactRepository->method('findByUserId')->willReturn(null);
        $this->userRepository->method('findById')->willReturn(null);

        $result = $this->resolver->resolve('user', self::USER_ID);

        self::assertSame(['email' => null, 'phone' => null, 'name' => null], $result);
    }

    public function testResolvePriorityEmployeeOverContact(): void
    {
        $employee = $this->createEmployee('employee@priority.com', '0991111111', 'Priority Employee');
        $contact = $this->createContact('contact@secondary.com', '0992222222', 'Secondary Contact');
        $userIdVO = UserIdValueObject::fromString(self::USER_ID);
        $this->employeeRepository->method('findByUserId')
            ->willReturnCallback(function (UserIdValueObject $id) use ($employee, $userIdVO) {
                return $id->equals($userIdVO) ? $employee : null;
            });
        $this->contactRepository->method('findByUserId')
            ->willReturnCallback(function (UserIdValueObject $id) use ($contact, $userIdVO) {
                return $id->equals($userIdVO) ? $contact : null;
            });

        $result = $this->resolver->resolve('user', self::USER_ID);

        self::assertSame('employee@priority.com', $result['email']);
        self::assertSame('Priority Employee', $result['name']);
    }

    private function createEmployee(string $email, string $phone, string $fullName): Employee
    {
        // Parse fullName into firstName and lastName
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? 'Doe';

        return Employee::hire(
            new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new EmployeeIdentificationNumberValueObject('1234567890'),
            $firstName,
            $lastName,
            new EmployeeEmailValueObject($email),
            $phone,
            new DateTimeImmutable('2024-01-15'),
        );
    }

    private function createContact(string $email, string $phone, string $fullName): Contact
    {
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? 'Doe';

        return Contact::create(
            new ContactIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA2'),
            new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA3'),
            $firstName,
            $lastName,
            new ContactEmailValueObject($email),
            new ContactPhoneNumberValueObject($phone),
            new ContactRoleValueObject('ADMIN'),
            // userId is optional (nullable) - null means no linked user
            null,
        );
    }

    private function createUser(string $username): User
    {
        return User::register(
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA4'),
            new UsernameValueObject($username),
            new HashedPasswordValueObject(password_hash('test', PASSWORD_DEFAULT)),
            new RoleIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA5'),
        );
    }
}
