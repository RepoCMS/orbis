<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Console;

use App\Client\Domain\Entities\Client;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Enums\EmployeeProfile;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTimeImmutable;

/**
 * Siembra datos demo realistas a través de múltiples Bounded Contexts
 * para entornos de desarrollo y pruebas.
 *
 * Uso:
 *   php bin/console app:seed:demo
 *
 * Idempotente: verifica existencia previa antes de crear cada entidad.
 * Se puede ejecutar múltiples veces sin duplicar datos.
 */
#[AsCommand(
    name: 'app:seed:demo',
    description: 'Crea datos demo para desarrollo/pruebas en todos los Bounded Contexts.',
    help: <<<HELP
Este comando siembra datos realistas para entornos de desarrollo:

  1. Roles:        admin + technician
  2. Permisos:     catálogo completo del sistema
  3. Employees:    3 perfiles (administrativo, técnico, supervisor)
  4. Usuarios:     admin y carlos, vinculados a sus employees
  5. Cliente:      Banco Test S.A. con 2 contactos
  6. Ticket:       incidencia correctiva de ejemplo
  7. Gastos:       liquidación de gastos + adelanto de efectivo

Idempotente: se puede ejecutar múltiples veces sin duplicar datos.
HELP
)]
final class SeedDemoDataCommand extends Command
{
    private const PASSWORD_PLAIN = 'Orbis2026!';

    // ── ULIDs hardcoded ──────────────────────────────────────────────────────

    // Roles
    private const ADMIN_ROLE_ULID = '01ARZ3NDEKTSV4RRFFQ69G5F01';
    private const TECH_ROLE_ULID  = '01ARZ3NDEKTSV4RRFFQ69G5F02';

    // Employees
    private const ADMIN_EMPLOYEE_ULID   = '01ARZ3NDEKTSV4RRFFQ69G5F10';
    private const CARLOS_EMPLOYEE_ULID  = '01ARZ3NDEKTSV4RRFFQ69G5F11';
    private const MARIA_EMPLOYEE_ULID   = '01ARZ3NDEKTSV4RRFFQ69G5F12';

    // Users
    private const ADMIN_USER_ULID   = '01ARZ3NDEKTSV4RRFFQ69G5F20';
    private const CARLOS_USER_ULID  = '01ARZ3NDEKTSV4RRFFQ69G5F21';

    // Client
    private const CLIENT_ULID = '01ARZ3NDEKTSV4RRFFQ69G5F30';

    // Contacts
    private const CONTACT_JUAN_ULID = '01ARZ3NDEKTSV4RRFFQ69G5F40';
    private const CONTACT_ANA_ULID  = '01ARZ3NDEKTSV4RRFFQ69G5F41';

    // Ticket
    private const TICKET_ULID = '01ARZ3NDEKTSV4RRFFQ69G5F50';

    // Expense
    private const OPERATING_EXPENSE_ULID = '01ARZ3NDEKTSV4RRFFQ69G5F60';
    private const CASH_ADVANCE_ULID      = '01ARZ3NDEKTSV4RRFFQ69G5F70';

    public function __construct(
        private readonly RoleRepositoryInterface $roleRepo,
        private readonly EmployeeRepositoryInterface $employeeRepo,
        private readonly UserRepositoryInterface $userRepo,
        private readonly ClientRepositoryInterface $clientRepo,
        private readonly ContactRepositoryInterface $contactRepo,
        private readonly OperatingExpenseRepositoryInterface $operatingExpenseRepo,
        private readonly CashAdvanceRepositoryInterface $cashAdvanceRepo,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Orbis — Demo Data Seeder');
        $io->text('Creando datos demo para desarrollo...');
        $io->newLine();

        // ── 1. Roles ─────────────────────────────────────────────────────────

        $rolesCreated = 0;

        $roleDefs = [
            ['id' => self::ADMIN_ROLE_ULID, 'name' => 'Administrador', 'slug' => 'admin'],
            ['id' => self::TECH_ROLE_ULID,  'name' => 'Técnico',       'slug' => 'technician'],
        ];

        foreach ($roleDefs as $def) {
            $slug = new RoleSlugValueObject($def['slug']);
            if ($this->roleRepo->findBySlug($slug) === null) {
                $role = Role::create(
                    id: new RoleIdValueObject($def['id']),
                    name: new RoleNameValueObject($def['name']),
                    slug: $slug,
                );
                $this->roleRepo->save($role);
                $rolesCreated++;
            }
        }

        $io->writeln(sprintf(
            '  <info>[OK]</info> Roles creados (%d)',
            $rolesCreated,
        ));

        // ── 2. Permissions ──────────────────────────────────────────────────

        try {
            $permCommand = $this->getApplication()->find('app:permissions:seed');
            $permCommand->run(new ArrayInput(['--quiet' => true, '--no-interaction' => true]), $output);
            $io->writeln('  <info>[OK]</info> Permisos sembrados');
        } catch (\Throwable $e) {
            $io->writeln(sprintf('  <comment>[!]</comment> Permisos: %s', $e->getMessage()));
        }

        // ── 3. Employees ────────────────────────────────────────────────────

        $employeesCreated = 0;

        $employeeDefs = [
            [
                'id'             => self::ADMIN_EMPLOYEE_ULID,
                'identification' => '1710034065',
                'firstName'      => 'Admin',
                'lastName'       => 'Sistema',
                'email'          => 'admin@orbis.cms.com.ec',
                'phone'          => '0990000001',
                'hiredAt'        => new DateTimeImmutable('2023-01-01'),
                'profile'        => EmployeeProfile::ADMINISTRATIVE,
            ],
            [
                'id'             => self::CARLOS_EMPLOYEE_ULID,
                'identification' => '1712345675',
                'firstName'      => 'Carlos',
                'lastName'       => 'Técnico',
                'email'          => 'carlos@orbis.cms.com.ec',
                'phone'          => '0990000002',
                'hiredAt'        => new DateTimeImmutable('2024-01-15'),
                'profile'        => EmployeeProfile::TECHNICIAN_FIELD,
            ],
            [
                'id'             => self::MARIA_EMPLOYEE_ULID,
                'identification' => '1723456784',
                'firstName'      => 'María',
                'lastName'       => 'Supervisora',
                'email'          => 'maria@orbis.cms.com.ec',
                'phone'          => '0990000003',
                'hiredAt'        => new DateTimeImmutable('2023-06-01'),
                'profile'        => EmployeeProfile::SUPERVISOR_TECHNICIAN,
            ],
        ];

        foreach ($employeeDefs as $def) {
            $empId = new EmployeeIdValueObject($def['id']);
            if ($this->employeeRepo->findById($empId) !== null) {
                continue;
            }
            // Verificar por cédula (unique constraint) antes de insertar
            $existing = $this->entityManager->createQueryBuilder()
                ->select('e.id')
                ->from(Employee::class, 'e')
                ->where('e.identificationNumber = :idn')
                ->setParameter('idn', $def['identification'])
                ->getQuery()
                ->getOneOrNullResult();
            if ($existing !== null) {
                continue;
            }
            $employee = Employee::hire(
                id: $empId,
                identificationNumber: new EmployeeIdentificationNumberValueObject($def['identification']),
                firstName: $def['firstName'],
                lastName: $def['lastName'],
                email: new EmployeeEmailValueObject($def['email']),
                phoneNumber: $def['phone'],
                hiredAt: $def['hiredAt'],
                profile: $def['profile'],
            );
            $this->employeeRepo->save($employee);
            $employeesCreated++;
        }

        $io->writeln(sprintf(
            '  <info>[OK]</info> Empleados creados (%d)',
            $employeesCreated,
        ));

        // ── 4. Users ────────────────────────────────────────────────────────

        $usersCreated = 0;

        $adminRole = $this->roleRepo->findBySlug(new RoleSlugValueObject('admin'));
        $techRole  = $this->roleRepo->findBySlug(new RoleSlugValueObject('technician'));

        $hashedPassword = new HashedPasswordValueObject(
            password_hash(self::PASSWORD_PLAIN, PASSWORD_BCRYPT),
        );

        $userDefs = [
            [
                'id'         => self::ADMIN_USER_ULID,
                'username'   => 'admin',
                'roleId'     => $adminRole?->id(),
                'employeeId' => self::ADMIN_EMPLOYEE_ULID,
            ],
            [
                'id'         => self::CARLOS_USER_ULID,
                'username'   => 'carlos',
                'roleId'     => $techRole?->id(),
                'employeeId' => self::CARLOS_EMPLOYEE_ULID,
            ],
        ];

        foreach ($userDefs as $def) {
            try {
                $username = new UsernameValueObject($def['username']);
                if ($this->userRepo->findByUsername($username) === null) {
                    $user = User::register(
                        id: new UserIdValueObject($def['id']),
                        username: $username,
                        password: $hashedPassword,
                        roleId: $def['roleId'],
                    );
                    $user->activate();
                    $this->userRepo->save($user);

                    // Link Employee ← User (relación inversa: Employee.userId apunta a User)
                    $empIdVo = new EmployeeIdValueObject($def['employeeId']);
                    $employee = $this->employeeRepo->findById($empIdVo);
                    if ($employee !== null) {
                        $employee->linkToUser((string) $user->id());
                        $this->employeeRepo->save($employee);
                    }
                    $usersCreated++;
                }
            } catch (\Throwable $e) {
                $io->writeln(sprintf('  <comment>[!]</comment> Usuario %s: %s', $def['username'], $e->getMessage()));
            }
        }

        $io->writeln(sprintf(
            '  <info>[OK]</info> Usuarios creados (%d)',
            $usersCreated,
        ));

        // ── 5. Client ───────────────────────────────────────────────────────

        $clientCreated = 0;
        try {
            $clientUlid = new ClientIdValueObject(self::CLIENT_ULID);
            $existingById = $this->clientRepo->findById($clientUlid);

            if ($existingById === null) {
                $clientRuc = new ClientRucValueObject('1790012344001');
                $existingByRuc = $this->clientRepo->findByRuc($clientRuc);

                if ($existingByRuc === null) {
                    $client = Client::register(
                        id: $clientUlid,
                        name: new ClientNameValueObject('Banco Test S.A.'),
                        ruc: $clientRuc,
                        address: 'Av. Principal 123, Quito',
                    );
                    $this->clientRepo->save($client);
                    $clientCreated++;
                }
            }
        } catch (\Throwable $e) {
            $io->writeln(sprintf('  <comment>[!]</comment> Cliente: %s', $e->getMessage()));
        }

        $clientIdVo = new ClientIdValueObject(self::CLIENT_ULID);

        $io->writeln(sprintf(
            '  <info>[OK]</info> Cliente creado (%d)',
            $clientCreated,
        ));

        // ── 6. Contacts ─────────────────────────────────────────────────────

        $contactsCreated = 0;

        $contactDefs = [
            [
                'id'        => self::CONTACT_JUAN_ULID,
                'firstName' => 'Juan',
                'lastName'  => 'Contacto',
                'email'     => 'juan@bancotest.com',
                'phone'     => '0990000010',
                'role'      => 'Técnico',
            ],
            [
                'id'        => self::CONTACT_ANA_ULID,
                'firstName' => 'Ana',
                'lastName'  => 'Contacto',
                'email'     => 'ana@bancotest.com',
                'phone'     => '0990000011',
                'role'      => 'Administrativo',
            ],
        ];

        foreach ($contactDefs as $def) {
            try {
                $contactId = new ContactIdValueObject($def['id']);
                if ($this->contactRepo->findById($contactId) === null) {
                    $contact = Contact::create(
                        id: $contactId,
                        clientId: $clientIdVo,
                        firstName: $def['firstName'],
                        lastName: $def['lastName'],
                        email: new ContactEmailValueObject($def['email']),
                        phoneNumber: new ContactPhoneNumberValueObject($def['phone']),
                        role: new ContactRoleValueObject($def['role']),
                    );
                    $this->contactRepo->save($contact);
                    $contactsCreated++;
                }
            } catch (\Throwable $e) {
                $io->writeln(sprintf('  <comment>[!]</comment> Contacto %s: %s', $def['firstName'], $e->getMessage()));
            }
        }

        $io->writeln(sprintf(
            '  <info>[OK]</info> Contactos creados (%d)',
            $contactsCreated,
        ));

        // ── 7. Ticket ───────────────────────────────────────────────────────
        // Omitido: Ticket::$assignments está tipado como `array` pero Doctrine
        // espera `Collection` para one-to-many. Pendiente refactor (Known Issue).

        // ── 8. Operating Expense ────────────────────────────────────────────

        $oeCreated = 0;
        try {
            $oeId = new OperatingExpenseIdValueObject(self::OPERATING_EXPENSE_ULID);
            if ($this->operatingExpenseRepo->findById($oeId) === null) {
                $oe = OperatingExpense::create(
                    id: $oeId,
                    sequence: 1,
                    employeeId: self::CARLOS_EMPLOYEE_ULID,
                    reason: 'Gastos de transporte y alimentación - Atención ticket TK-2026-000001',
                    startDate: new DateTimeImmutable('2026-05-22'),
                    endDate: new DateTimeImmutable('2026-05-23'),
                );
                $this->operatingExpenseRepo->save($oe);
                $oeCreated++;
            }
        } catch (\Throwable $e) {
            $io->writeln(sprintf('  <comment>[!]</comment> Liquidación: %s', $e->getMessage()));
        }

        $io->writeln(sprintf(
            '  <info>[OK]</info> Liquidación de gastos creada (%d)',
            $oeCreated,
        ));

        // ── 9. Cash Advance ─────────────────────────────────────────────────

        $caCreated = 0;
        try {
            $caId = new CashAdvanceIdValueObject(self::CASH_ADVANCE_ULID);
            if ($this->cashAdvanceRepo->findById($caId) === null) {
                $ca = CashAdvance::create(
                    id: $caId,
                    advanceNumber: 'CA-2026-00001',
                    employeeId: self::CARLOS_EMPLOYEE_ULID,
                    givenBy: self::ADMIN_EMPLOYEE_ULID,
                    amount: 150.00,
                    givenAt: new DateTimeImmutable('2026-05-20'),
                );
                $this->cashAdvanceRepo->save($ca);
                $caCreated++;
            }
        } catch (\Throwable $e) {
            $io->writeln(sprintf('  <comment>[!]</comment> Adelanto: %s', $e->getMessage()));
        }

        // ── Summary ─────────────────────────────────────────────────────────

        $io->newLine(2);
        $io->section('Resumen');

        $io->definitionList(
            ['Roles'             => sprintf('%d creados',             $rolesCreated)],
            ['Permisos'          => 'Sembrados del catálogo completo'],
            ['Empleados'         => sprintf('%d creados',             $employeesCreated)],
            ['Usuarios'          => sprintf('%d creados',             $usersCreated)],
            ['Cliente'           => sprintf('%d creado con contactos',$clientCreated + $contactsCreated)],
            ['Ticket'            => 'Omitido (Known Issue: Ticket::$assignments type)'],
            ['Liquidación gastos'=> sprintf('%d creada',              $oeCreated)],
            ['Adelanto efectivo' => sprintf('%d creado',              $caCreated)],
        );

        $io->newLine();
        $io->section('Credenciales de acceso');

        $io->table(
            ['Usuario', 'Password', 'Rol'],
            [
                ['admin',  self::PASSWORD_PLAIN, 'Administrador'],
                ['carlos', self::PASSWORD_PLAIN, 'Técnico'],
            ],
        );

        $totalCreated = $rolesCreated + $employeesCreated + $usersCreated
            + $clientCreated + $contactsCreated
            + $oeCreated + $caCreated;

        $io->success(sprintf(
            'Demo data completada. %d entidades creadas.',
            $totalCreated,
        ));

        return Command::SUCCESS;
    }
}
