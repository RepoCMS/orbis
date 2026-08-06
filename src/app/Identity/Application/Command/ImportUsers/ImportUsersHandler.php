<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ImportUsers;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Infrastructure\Import\UserCsvFileParser;
use App\Identity\Infrastructure\Import\UserRowValidator;
use App\Shared\Application\Import\ImportResult;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ImportUsersHandler
{
    public function __construct(
        private UserCsvFileParser $parser,
        private UserRowValidator $rowValidator,
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
        private PasswordHasherInterface $passwordHasher,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ImportUsersCommand $command): ImportResult
    {
        $rows = $this->parser->parse($command->filePath);

        if (empty($rows)) {
            return new ImportResult(0, 0, 0, ['Archivo vacío o sin datos']);
        }

        $missingHeaders = $this->parser->validateHeaders(array_keys($rows[0]));
        if ($missingHeaders !== []) {
            return new ImportResult(0, 0, 0, [
                sprintf('Cabeceras faltantes: %s', implode(', ', $missingHeaders)),
            ]);
        }

        $imported = 0;
        $errors = 0;
        $skipped = 0;
        $errorDetail = [];

        foreach ($rows as $idx => $row) {
            $rowNumber = $idx + 2;

            $validationErrors = $this->rowValidator->validate($row, $rowNumber);
            if ($validationErrors !== []) {
                $errors++;
                $errorDetail = array_merge($errorDetail, $validationErrors);
                continue;
            }

            try {
                $username = new UsernameValueObject(trim($row['username']));
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Username inválido: %s', $rowNumber, $e->getMessage());
                continue;
            }

            $existing = $this->userRepository->findByUsername($username);
            if ($existing !== null) {
                $skipped++;
                $errorDetail[] = sprintf('Fila %d: Username "%s" ya existe', $rowNumber, $row['username']);
                continue;
            }

            try {
                $roleSlug = new RoleSlugValueObject(trim($row['role_slug']));
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Role slug inválido: %s', $rowNumber, $e->getMessage());
                continue;
            }

            $role = $this->roleRepository->findBySlug($roleSlug);
            if ($role === null) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Role con slug "%s" no encontrado', $rowNumber, $row['role_slug']);
                continue;
            }

            $rawPasswordStr = trim($row['raw_password'] ?? '');
            if ($rawPasswordStr === '') {
                $rawPasswordStr = 'Abcdef1!' . bin2hex(random_bytes(4));
            }

            try {
                $hashedPassword = $this->passwordHasher->hash(new RawPasswordValueObject($rawPasswordStr));
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Contraseña inválida: %s', $rowNumber, $e->getMessage());
                continue;
            }

            if ($command->dryRun) {
                $imported++;
                continue;
            }

            try {
                $userId = new UserIdValueObject($this->idGenerator->generate());
                $user = User::register(
                    id: $userId,
                    username: $username,
                    password: $hashedPassword,
                    roleId: new RoleIdValueObject((string) $role->id()),
                );

                $linkedEmployeeId = trim($row['linked_employee_id'] ?? '');
                if ($linkedEmployeeId !== '') {
                    $user->linkEmployee($linkedEmployeeId);
                }

                $this->userRepository->save($user);

                $this->eventPublisher->publish(new UserRegistered(
                    userId: (string) $userId,
                    username: (string) $username,
                    roleId: (string) $role->id(),
                ));

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $errorDetail[] = sprintf('Fila %d: Error al crear usuario: %s', $rowNumber, $e->getMessage());
            }
        }

        return new ImportResult($imported, $errors, $skipped, $errorDetail);
    }
}
