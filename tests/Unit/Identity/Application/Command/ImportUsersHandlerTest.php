<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ImportUsers\ImportUsersCommand;
use App\Identity\Application\Command\ImportUsers\ImportUsersHandler;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Infrastructure\Import\UserCsvFileParser;
use App\Identity\Infrastructure\Import\UserRowValidator;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\Import\CsvFileParser;
use PHPUnit\Framework\TestCase;

final class ImportUsersHandlerTest extends TestCase
{
    private const GENERATED_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        file_put_contents($path, $content);

        return $path;
    }

    private function createRole(): Role
    {
        return Role::create(
            id: new RoleIdValueObject(self::GENERATED_ID),
            name: new RoleNameValueObject('test_role'),
            slug: new RoleSlugValueObject('test_role'),
        );
    }

    private function createHandler(
        UserRepositoryInterface $userRepo,
        RoleRepositoryInterface $roleRepo,
        PasswordHasherInterface $passwordHasher,
        IdGeneratorInterface $idGenerator,
        DomainEventPublisherInterface $eventPublisher,
    ): ImportUsersHandler {
        return new ImportUsersHandler(
            new UserCsvFileParser(new CsvFileParser()),
            new UserRowValidator(),
            $userRepo,
            $roleRepo,
            $passwordHasher,
            $idGenerator,
            $eventPublisher,
        );
    }

    private function defaultMocks(): array
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findBySlug')->willReturn($this->createRole());

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(new HashedPasswordValueObject('hashed_pwd'));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        return [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher];
    }

    public function testImportsUsersSuccessfully(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\njuan.perez,test_role,Pass1234!,EMP001\nmaria.lopez,test_role,Pass5678!,EMP002";
        $path = $this->createTempCsv($csv);

        [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher] = $this->defaultMocks();

        $userRepo->expects($this->exactly(2))->method('save');
        $eventPublisher->expects($this->exactly(2))->method('publish')
            ->with($this->isInstanceOf(UserRegistered::class));

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(2, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testGeneratesPasswordWhenEmpty(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\njuan.perez,test_role,,EMP001";
        $path = $this->createTempCsv($csv);

        [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher] = $this->defaultMocks();

        $passwordHasher->expects($this->once())->method('hash')
            ->with($this->isInstanceOf(RawPasswordValueObject::class))
            ->willReturn(new HashedPasswordValueObject('hashed_pwd'));
        $userRepo->expects($this->once())->method('save');
        $eventPublisher->expects($this->once())->method('publish');

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(1, $result->imported);
        self::assertSame(0, $result->errors);
    }

    public function testSkipsDuplicateUsername(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\njuan.perez,test_role,Pass1234!,EMP001";
        $path = $this->createTempCsv($csv);

        $existingUser = User::register(
            id: new UserIdValueObject(self::GENERATED_ID),
            username: new UsernameValueObject('juan.perez'),
            password: new HashedPasswordValueObject('hashed'),
            roleId: new RoleIdValueObject(self::GENERATED_ID),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn($existingUser);

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findBySlug')->willReturn($this->createRole());

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(new HashedPasswordValueObject('hashed_pwd'));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(1, $result->skipped);
    }

    public function testErrorsWhenRoleNotFound(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\njuan.perez,bad_role,Pass1234!,EMP001";
        $path = $this->createTempCsv($csv);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findBySlug')->willReturn(null);

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->method('hash')->willReturn(new HashedPasswordValueObject('hashed_pwd'));

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(1, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\njuan.perez,test_role,Pass1234!,EMP001";
        $path = $this->createTempCsv($csv);

        [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher] = $this->defaultMocks();
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path, dryRun: true));

        self::assertSame(1, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnInvalidRows(): void
    {
        $csv = "username,role_slug,raw_password,linked_employee_id\nab,admin,weak,EMP001\njuan.perez,,Pass1234,EMP002";
        $path = $this->createTempCsv($csv);

        [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher] = $this->defaultMocks();
        $userRepo->expects($this->never())->method('save');

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(2, $result->errors);
        self::assertSame(0, $result->skipped);
    }

    public function testReturnsErrorOnMissingHeaders(): void
    {
        $csv = "unknown,fields\nvalue1,value2";
        $path = $this->createTempCsv($csv);

        [$userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher] = $this->defaultMocks();

        $handler = $this->createHandler($userRepo, $roleRepo, $passwordHasher, $idGenerator, $eventPublisher);
        $result = ($handler)(new ImportUsersCommand(filePath: $path));

        self::assertSame(0, $result->imported);
        self::assertSame(0, $result->errors);
        self::assertSame(0, $result->skipped);
        self::assertNotEmpty($result->errorDetail);
    }
}
