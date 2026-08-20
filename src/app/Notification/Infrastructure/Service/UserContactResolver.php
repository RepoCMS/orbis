<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Notification\Domain\Services\ContactResolverInterface;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Resuelve datos de contacto para RecipientType::USER.
 *
 * Para un userId dado, busca en:
 *   1. Employee (Organization BC) — empleados vinculados a un usuario
 *   2. Contact (Client BC) — contactos de cliente vinculados a un usuario
 *   3. User (Identity BC) — fallback mínimo (username como email)
 */
final readonly class UserContactResolver implements ContactResolverInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function resolve(string $recipientType, string $recipientId): array
    {
        if ($recipientType !== 'user') {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        $userId = UserIdValueObject::fromString($recipientId);

        // 1) Buscar como Employee vinculado
        $employee = $this->employeeRepository->findByUserId($userId);
        if ($employee !== null) {
            return [
                'email' => (string) $employee->email(),
                'phone' => $employee->phoneNumber(),
                'name' => $employee->fullName(),
            ];
        }

        // 2) Buscar como Contact vinculado
        $contact = $this->contactRepository->findByUserId($userId);
        if ($contact !== null) {
            return [
                'email' => (string) $contact->email(),
                'phone' => (string) $contact->phoneNumber(),
                'name' => $contact->fullName(),
            ];
        }

        // 3) Fallback: solo tenemos username del User
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        return [
            'email' => (string) $user->username(),
            'phone' => null,
            'name' => (string) $user->username(),
        ];
    }
}
