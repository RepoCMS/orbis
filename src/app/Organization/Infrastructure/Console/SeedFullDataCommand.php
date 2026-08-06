<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Console;

use App\Client\Domain\Entities\Client;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Entities\TerminalAssignment;
use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\Repositories\TerminalAssignmentRepositoryInterface;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\SiteIdValueObject;
use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Expense\Domain\Entities\CashAdvance;
use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Enums\ProductTrackingType;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\RepairRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Location\Domain\Repositories\CityRepositoryInterface;
use App\Organization\Domain\Entities\ServiceZone;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\ServiceZoneRepositoryInterface;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketAssignmentRepositoryInterface;
use App\Support\Domain\Repositories\TicketFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:full',
    description: 'Pobla toda la BD con datos realistas para test (clientes, terminales, productos, tickets, reparaciones, etc).',
)]
final class SeedFullDataCommand extends Command
{
    private const PASSWORD_PLAIN = 'Orbis2026!';
    private const ADMIN_USERNAME = 'admin';
    private const TECH_USERNAME = 'carlos';

    private SymfonyStyle $io;
    private int $total = 0;

    public function __construct(
        // Shared
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $doctrine,
        private readonly IdGeneratorInterface $idGenerator,
        private readonly CityRepositoryInterface $cityRepo,
        // Client BC
        private readonly ClientRepositoryInterface $clientRepo,
        private readonly ContactRepositoryInterface $contactRepo,
        private readonly TerminalRepositoryInterface $terminalRepo,
        private readonly TerminalAssignmentRepositoryInterface $terminalAssignmentRepo,
        // Organization BC
        private readonly EmployeeRepositoryInterface $employeeRepo,
        private readonly ServiceZoneRepositoryInterface $serviceZoneRepo,
        private readonly RoleRepositoryInterface $roleRepo,
        private readonly UserRepositoryInterface $userRepo,
        // Inventory BC
        private readonly ProductRepositoryInterface $productRepo,
        private readonly LocationRepositoryInterface $locationRepo,
        private readonly SerializedStockRepositoryInterface $serializedStockRepo,
        private readonly NonserializedStockRepositoryInterface $nonserializedStockRepo,
        private readonly RepairRepositoryInterface $repairRepo,
        private readonly MovementRepositoryInterface $movementRepo,
        // Support BC
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly TicketAssignmentRepositoryInterface $ticketAssignmentRepo,
        private readonly TicketFindingRepositoryInterface $ticketFindingRepo,
        // Expense BC
        private readonly OperatingExpenseRepositoryInterface $operatingExpenseRepo,
        private readonly CashAdvanceRepositoryInterface $cashAdvanceRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<HELP
Pobla toda la base de datos con datos realistas para entorno de test:

  1. Geografía de Ecuador
  2. Roles, permisos, empleados, usuarios
  3. Modelos de terminales (cajeros)
  4. Clientes adicionales con contactos
  5. Terminales asignadas a cada cliente
  6. Productos (repuestos) con stock en bodega
  7. Ubicaciones (bodega, laboratorio, mochilas)
  8. Zonas de servicio con técnicos asignados
  9. Tickets en varios estados del ciclo de vida
  10. Liquidaciones de gastos y adelantos
  11. Reparaciones con evidencias y consumibles
  12. Movimientos de stock entre ubicaciones
  13. Stock dado de baja / dañado

Idempotente: se puede ejecutar múltiples veces sin duplicar datos.
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Orbis — Full Data Seeder');

        $this->runSeedCommand('app:seed:geography', $input, $output);
        $this->runSeedCommand('app:seed:demo', $input, $output);

        if (!$this->entityManager->isOpen()) {
            $this->doctrine->resetManager();
        }

        $this->createTerminalModels();
        $this->createAdditionalClients();
        $this->createTerminalAssignments();
        $this->createProducts();
        $this->createLocations();
        $this->createStock();
        $this->createServiceZones();
        $this->createTickets();
        $this->createRepairs();
        $this->createMovements();
        $this->createDecommissionedStock();
        $this->createAdditionalExpenses();

        $this->entityManager->flush();

        $this->io->newLine(2);
        $this->io->success(sprintf('Full data seeding completado. %d entidades creadas.', $this->total));

        return Command::SUCCESS;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function runSeedCommand(string $commandName, InputInterface $input, OutputInterface $output): void
    {
        try {
            $cmd = $this->getApplication()->find($commandName);
            $cmd->run(new ArrayInput(['--quiet' => true, '--no-interaction' => true]), $output);
        } catch (\Throwable $e) {
            if ($this->io->isVerbose()) {
                $this->io->writeln(sprintf('  <comment>[!]</comment> %s: %s', $commandName, $e->getMessage()));
            }
        }
    }

    private function cityByName(string $name): ?string
    {
        static $cache = [];
        if (!isset($cache[$name])) {
            $city = $this->cityRepo->findByName($name);
            if ($city !== null) {
                $cache[$name] = (string) $city->id();
            }
        }

        return $cache[$name] ?? null;
    }

    private function ok(int $count, string $label): void
    {
        if ($count > 0) {
            $this->total += $count;
            $this->io->writeln(sprintf('  <info>✓ %s</info> (%d)', $label, $count));
        }
    }

    private function existingEmployee(string $username): ?string
    {
        $user = $this->userRepo->findByUsername(new \App\Identity\Domain\ValueObjects\UsernameValueObject($username));
        if ($user === null) {
            return null;
        }
        $employee = $this->employeeRepo->findByUserId($user->id());

        return $employee !== null ? (string) $employee->id() : null;
    }

    private function userIdByUsername(string $username): ?UserIdValueObject
    {
        $user = $this->userRepo->findByUsername(new \App\Identity\Domain\ValueObjects\UsernameValueObject($username));

        return $user !== null ? new UserIdValueObject((string) $user->id()) : null;
    }

    /** Encuentra un Location por nombre exacto */
    private function locationByName(string $name): ?string
    {
        $all = $this->locationRepo->findAll();
        foreach ($all as $loc) {
            if ((string) $loc->name() === $name) {
                return (string) $loc->id();
            }
        }

        return null;
    }

    /** Encuentra un producto por código de proveedor */
    private function productByCode(string $code): ?Product
    {
        return $this->productRepo->findByCode(new ProductCodeAssignedBySupplierValueObject($code));
    }

    // ── 1. Terminal Models ────────────────────────────────────────────────

    private function createTerminalModels(): void
    {
        $models = [
            ['serial' => 'NCR6626001', 'vendor' => 'ncr', 'func' => 'multinfunction', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'NCR6626002', 'vendor' => 'ncr', 'func' => 'multinfunction', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'DBD7500001', 'vendor' => 'diebold', 'func' => 'dispenser', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'DBD7500002', 'vendor' => 'diebold', 'func' => 'dispenser', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'HYO7800001', 'vendor' => 'hyosung', 'func' => 'multinfunction', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'HYO7800002', 'vendor' => 'hyosung', 'func' => 'multinfunction', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'NCR6650001', 'vendor' => 'ncr', 'func' => 'recycler', 'type' => 'lobby', 'client_idx' => 0],
            ['serial' => 'GRG3000001', 'vendor' => 'grg', 'func' => 'dispenser', 'type' => 'lobby', 'client_idx' => 0],
        ];

        $count = 0;
        foreach ($models as $m) {
            $existing = $this->terminalRepo->findBySerial(new SerialNumberValueObject($m['serial']));
            if ($existing !== null) {
                continue;
            }

            $terminal = Terminal::add(
                id: new TerminalIdValueObject($this->idGenerator->generate()),
                clientId: new ClientIdValueObject('00EMPTY0000000000000000000'),
                serial: new SerialNumberValueObject($m['serial']),
                vendor: TerminalVendor::from($m['vendor']),
                funcionality: TerminalFunctionality::from($m['func']),
                type: TerminalType::from($m['type']),
                status: TerminalStatus::ACTIVE,
            );
            $this->terminalRepo->save($terminal);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'modelos de terminal');
    }

    // ── 2. Additional Clients ─────────────────────────────────────────────

    private function createAdditionalClients(): void
    {
        $clients = [
            [
                'ruc' => '1790012344001', 'name' => 'Banco del Pacífico S.A.',
                'address' => 'Av. 9 de Octubre 100, Guayaquil',
                'contacts' => [
                    ['fn' => 'Pedro', 'ln' => 'Gómez', 'email' => 'pgomez@bancopacifico.com', 'phone' => '0992000010', 'role' => 'Técnico'],
                    ['fn' => 'Lucía', 'ln' => 'Mendoza', 'email' => 'lmendoza@bancopacifico.com', 'phone' => '0992000011', 'role' => 'Coordinador'],
                ],
            ],
            [
                'ruc' => '1790098761001', 'name' => 'Cooperativa Ahorro Nacional',
                'address' => 'Calle Larga 4-50, Cuenca',
                'contacts' => [
                    ['fn' => 'Diego', 'ln' => 'Torres', 'email' => 'dtorres@coopahorro.fin.ec', 'phone' => '0993000020', 'role' => 'Técnico'],
                ],
            ],
            [
                'ruc' => '1790045676001', 'name' => 'Financiera Rapidín S.A.',
                'address' => 'Av. Malecón 200, Manta',
                'contacts' => [
                    ['fn' => 'Carmen', 'ln' => 'Vera', 'email' => 'cvera@financierarapidin.com', 'phone' => '0994000030', 'role' => 'Administrativo'],
                    ['fn' => 'Jorge', 'ln' => 'Loor', 'email' => 'jloor@financierarapidin.com', 'phone' => '0994000031', 'role' => 'Técnico'],
                ],
            ],
        ];

        $clientCount = 0;
        $contactCount = 0;

        foreach ($clients as $c) {
            $ruc = new ClientRucValueObject($c['ruc']);
            if ($this->clientRepo->findByRuc($ruc) !== null) {
                continue;
            }

            $client = Client::register(
                id: new ClientIdValueObject($this->idGenerator->generate()),
                name: new ClientNameValueObject($c['name']),
                ruc: $ruc,
                address: $c['address'],
            );
            $this->clientRepo->save($client);
            $this->entityManager->flush();
            $clientCount++;

            foreach ($c['contacts'] as $co) {
                $contact = Contact::create(
                    id: new ContactIdValueObject($this->idGenerator->generate()),
                    clientId: new ClientIdValueObject((string) $client->id()),
                    firstName: $co['fn'],
                    lastName: $co['ln'],
                    email: new ContactEmailValueObject($co['email']),
                    phoneNumber: new ContactPhoneNumberValueObject($co['phone']),
                    role: new ContactRoleValueObject($co['role']),
                );
                $this->contactRepo->save($contact);
                $contactCount++;
            }
            $this->entityManager->flush();
        }

        $this->ok($clientCount, 'clientes adicionales');
        $this->ok($contactCount, 'contactos');
    }

    // ── 3. Terminal Assignments ───────────────────────────────────────────

    private function createTerminalAssignments(): void
    {
        // Force connection initialization to register Doctrine custom types
        $this->entityManager->getConnection();

        // getAllTerminals helper via EntityManager (TerminalRepo has no findAll)
        $terminals = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Terminal::class, 't')
            ->getQuery()
            ->getResult();
        if (empty($terminals)) {
            return;
        }

        $allClients = [];
        $rucs = ['1790012344001', '1790098761001', '1790045676001'];
        foreach ($rucs as $ruc) {
            $c = $this->clientRepo->findByRuc(new ClientRucValueObject($ruc));
            if ($c !== null) {
                $allClients[] = $c;
            }
        }
        if (empty($allClients)) {
            return;
        }

        // Build a lookup of existing assignments by terminal ID
        $existingAssignmentsByTerminal = [];
        foreach ($this->terminalAssignmentRepo->findAll() as $existing) {
            $existingAssignmentsByTerminal[(string) $existing->terminalId()] = true;
        }

        $assignmentsData = [
            ['term_serial' => 'NCR6626001', 'client_idx' => 0, 'name' => 'ATM Centro Guayaquil', 'id_by_client' => 'TERMPAC001', 'location' => 'Guayaquil'],
            ['term_serial' => 'NCR6626002', 'client_idx' => 0, 'name' => 'ATM Norte Guayaquil', 'id_by_client' => 'TERMPAC002', 'location' => 'Guayaquil'],
            ['term_serial' => 'DBD7500001', 'client_idx' => 0, 'name' => 'ATM Sur Guayaquil', 'id_by_client' => 'TERMPAC003', 'location' => 'Guayaquil'],
            ['term_serial' => 'DBD7500002', 'client_idx' => 0, 'name' => 'ATM Durán', 'id_by_client' => 'TERMPAC004', 'location' => 'Durán'],
            ['term_serial' => 'HYO7800001', 'client_idx' => 1, 'name' => 'ATM Cuenca Centro', 'id_by_client' => 'TERMCOOP01', 'location' => 'Cuenca'],
            ['term_serial' => 'HYO7800002', 'client_idx' => 1, 'name' => 'ATM Cuenca Norte', 'id_by_client' => 'TERMCOOP02', 'location' => 'Cuenca'],
            ['term_serial' => 'NCR6650001', 'client_idx' => 2, 'name' => 'ATM Manta Centro', 'id_by_client' => 'TERMRAP001', 'location' => 'Manta'],
            ['term_serial' => 'GRG3000001', 'client_idx' => 2, 'name' => 'ATM Manta Sur', 'id_by_client' => 'TERMRAP002', 'location' => 'Manta'],
        ];

        $count = 0;
        foreach ($assignmentsData as $ad) {
            $terminal = $this->terminalRepo->findBySerial(new SerialNumberValueObject($ad['term_serial']));
            if ($terminal === null) {
                continue;
            }
            $client = $allClients[$ad['client_idx']] ?? null;
            if ($client === null) {
                continue;
            }

            $termId = (string) $terminal->id();
            if (isset($existingAssignmentsByTerminal[$termId])) {
                continue;
            }

            $cityId = $this->cityByName($ad['location']) ?? $this->cityByName('Guayaquil');
            $assignment = TerminalAssignment::add(
                id: new TerminalAssignmentIdValueObject($this->idGenerator->generate()),
                terminalId: new TerminalIdValueObject($termId),
                name: new TerminalNameValueObject($ad['name']),
                idAssingedByClient: new TerminalIdAssignedByClientValueObject($ad['id_by_client']),
                siteId: new SiteIdValueObject($cityId ?? ''),
                status: TerminalAssignmentStatus::OPERATIVE,
            );
            $this->terminalAssignmentRepo->save($assignment);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'asignaciones de terminales');
    }

    // ── 4. Products (repuestos) ───────────────────────────────────────────

    private function createProducts(): void
    {
        $products = [
            ['code' => 'SENS-IR-001', 'name' => 'Sensor infrarrojo MX', 'desc' => 'Sensor de proximidad para dispensador NCR', 'tracking' => 'serialized'],
            ['code' => 'DISP-BILL-001', 'name' => 'Dispensador de billetes NCR', 'desc' => 'Módulo dispensador para NCR 6626', 'tracking' => 'serialized'],
            ['code' => 'TECL-ATM-001', 'name' => 'Teclado ATM EPP', 'desc' => 'Teclado con lector de tarjetas', 'tracking' => 'serialized'],
            ['code' => 'PANT-15-001', 'name' => 'Pantalla LCD 15"', 'desc' => 'Monitor LCD 15 pulgadas para ATM', 'tracking' => 'serialized'],
            ['code' => 'FUENTE-12V-001', 'name' => 'Fuente poder 12V', 'desc' => 'Fuente de alimentación 12V 5A', 'tracking' => 'non_serialized'],
            ['code' => 'CABLE-USB-001', 'name' => 'Cable USB blindado', 'desc' => 'Cable USB 2.0 blindado 3m', 'tracking' => 'non_serialized'],
            ['code' => 'VENT-80-001', 'name' => 'Ventilador 80x80', 'desc' => 'Ventilador de refrigeración 80mm', 'tracking' => 'non_serialized'],
            ['code' => 'ROLLO-PAPEL-001', 'name' => 'Rollo papel térmico', 'desc' => 'Rollo papel térmico 80x80mm', 'tracking' => 'non_serialized'],
            ['code' => 'CINTA-IMP-001', 'name' => 'Cinta impresora', 'desc' => 'Cinta para impresora térmica Epson', 'tracking' => 'non_serialized'],
            ['code' => 'BAND-5B-001', 'name' => 'Bandeja de 5 billetes', 'desc' => 'Bandeja dispensadora 5 billetes', 'tracking' => 'serialized'],
            ['code' => 'LECTOR-TARJ-001', 'name' => 'Lector de tarjetas MSR', 'desc' => 'Lector de banda magnética triple pista', 'tracking' => 'serialized'],
            ['code' => 'MOD-SIM-001', 'name' => 'Módulo SIM GSM', 'desc' => 'Módulo de comunicación GSM para ATM', 'tracking' => 'serialized'],
            ['code' => 'BAT-LIT-001', 'name' => 'Batería litio CR2032', 'desc' => 'Batería para placa madre ATM', 'tracking' => 'non_serialized'],
            ['code' => 'FUS-10A-001', 'name' => 'Fusible 10A', 'desc' => 'Fusible cerámico 10A 250V', 'tracking' => 'non_serialized'],
            ['code' => 'CER-PUE-001', 'name' => 'Cerradura de puerta', 'desc' => 'Cerradura de seguridad para cajero', 'tracking' => 'non_serialized'],
        ];

        $count = 0;
        foreach ($products as $p) {
            $code = new ProductCodeAssignedBySupplierValueObject($p['code']);
            if ($this->productRepo->findByCode($code) !== null) {
                continue;
            }

            $product = Product::add(
                id: new ProductIdValueObject($this->idGenerator->generate()),
                productCode: $code,
                name: new ProductNameValueObject($p['name']),
                description: $p['desc'],
                trackingType: ProductTrackingType::from($p['tracking']),
            );
            $this->productRepo->save($product);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'productos/repuestos');
    }

    // ── 5. Locations ──────────────────────────────────────────────────────

    private function createLocations(): void
    {
        $quitoId = $this->cityByName('Quito');
        $guayaquilId = $this->cityByName('Guayaquil');
        $cuencaId = $this->cityByName('Cuenca');
        $mantaId = $this->cityByName('Manta');

        $carlosEmpId = $this->existingEmployee(self::TECH_USERNAME);
        $mariaEmpId = null;
        $maria = $this->employeeRepo->findByEmail('maria@orbis.cms.com.ec');
        if ($maria !== null) {
            $mariaEmpId = (string) $maria->id();
        }

        $locations = [
            ['type' => 'warehouse', 'name' => 'Bodega Central Quito', 'city' => $quitoId, 'address' => 'Av. América 500', 'employeeId' => null],
            ['type' => 'laboratory', 'name' => 'Laboratorio Técnico Quito', 'city' => $quitoId, 'address' => 'Calle Rumiñahui 200', 'employeeId' => null],
            ['type' => 'laboratory', 'name' => 'Laboratorio Guayaquil', 'city' => $guayaquilId, 'address' => 'Av. del Bombero 300', 'employeeId' => null],
            ['type' => 'backpack', 'name' => 'Mochila Carlos Técnico', 'city' => $quitoId, 'address' => 'N/A', 'employeeId' => $carlosEmpId],
            ['type' => 'backpack', 'name' => 'Mochila María Supervisora', 'city' => $guayaquilId, 'address' => 'N/A', 'employeeId' => $mariaEmpId],
            ['type' => 'warehouse', 'name' => 'Bodega Guayaquil', 'city' => $guayaquilId, 'address' => 'Av. 9 de Octubre 1500', 'employeeId' => null],
            ['type' => 'warehouse', 'name' => 'Bodega Cuenca', 'city' => $cuencaId, 'address' => 'Av. España 300', 'employeeId' => null],
            ['type' => 'warehouse', 'name' => 'Bodega Manta', 'city' => $mantaId, 'address' => 'Av. Malecón 400', 'employeeId' => null],
        ];

        $count = 0;
        foreach ($locations as $l) {
            $loc = Location::create(
                id: new LocationIdValueObject($this->idGenerator->generate()),
                name: new LocationNameValueObject($l['name']),
                city: $l['city'],
                address: $l['address'],
                type: LocationType::from($l['type']),
                employeeId: $l['employeeId'] ?? null,
            );
            $this->locationRepo->save($loc);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'ubicaciones');
    }

    // ── 6. Stock ──────────────────────────────────────────────────────────

    private function createStock(): void
    {
        $bodegaCentral = $this->locationByName('Bodega Central Quito');
        if ($bodegaCentral === null) {
            return;
        }

        $allProducts = $this->productRepo->findAll();
        $serialCount = 0;
        $nonserialCount = 0;

        foreach ($allProducts as $product) {
            if ($product->trackingType() === ProductTrackingType::SERIALIZED) {
                $existing = $this->serializedStockRepo->findByProduct(new ProductIdValueObject((string) $product->id()));
                if (!empty($existing)) {
                    continue;
                }

                $stock = SerializedStock::register(
                    id: new SerializedStockIdValueObject($this->idGenerator->generate()),
                    productId: new ProductIdValueObject((string) $product->id()),
                    serialNumber: new StockSerialNumberValueObject('SN-' . strtoupper(substr(md5((string) $product->id()), 0, 8))),
                    locationId: new LocationIdValueObject($bodegaCentral),
                );
                $this->serializedStockRepo->save($stock);
                $serialCount++;
            } else {
                $existing = $this->nonserializedStockRepo->findByProduct(new ProductIdValueObject((string) $product->id()));
                if (!empty($existing)) {
                    continue;
                }

                $stock = NonserializedStock::initialize(
                    id: new NonserializedStockIdValueObject($this->idGenerator->generate()),
                    productId: new ProductIdValueObject((string) $product->id()),
                    locationId: new LocationIdValueObject($bodegaCentral),
                    initialQuantity: random_int(5, 50),
                );
                $this->nonserializedStockRepo->save($stock);
                $nonserialCount++;
            }
        }
        $this->entityManager->flush();
        $this->ok($serialCount, 'stock serializado');
        $this->ok($nonserialCount, 'stock no serializado');
    }

    // ── 7. Service Zones ──────────────────────────────────────────────────

    private function createServiceZones(): void
    {
        $quitoId = $this->cityByName('Quito');
        $guayaquilId = $this->cityByName('Guayaquil');
        $cuencaId = $this->cityByName('Cuenca');
        $mantaId = $this->cityByName('Manta');
        $sangolquiId = $this->cityByName('Sangolquí');
        $duránId = $this->cityByName('Durán');

        $adminId = $this->existingEmployee(self::ADMIN_USERNAME);
        $carlosId = $this->existingEmployee(self::TECH_USERNAME);
        $maria = $this->employeeRepo->findByEmail('maria@orbis.cms.com.ec');
        $mariaId = $maria !== null ? (string) $maria->id() : null;

        $zones = [
            ['name' => 'Zona Norte Quito', 'cities' => array_filter([$quitoId, $sangolquiId]), 'empId' => $carlosId],
            ['name' => 'Zona Sur Quito', 'cities' => array_filter([$quitoId]), 'empId' => $adminId],
            ['name' => 'Zona Guayaquil', 'cities' => array_filter([$guayaquilId, $duránId]), 'empId' => $mariaId],
            ['name' => 'Zona Cuenca', 'cities' => array_filter([$cuencaId]), 'empId' => null],
            ['name' => 'Zona Manta', 'cities' => array_filter([$mantaId]), 'empId' => null],
        ];

        $allZones = $this->serviceZoneRepo->findAll();
        $existingZoneNames = array_map(static fn (ServiceZone $z) => (string) $z->name(), $allZones);

        $count = 0;
        foreach ($zones as $z) {
            if (in_array($z['name'], $existingZoneNames, true)) {
                continue;
            }

            $zone = ServiceZone::create(
                id: new ServiceZoneIdValueObject($this->idGenerator->generate()),
                name: $z['name'],
            );
            foreach ($z['cities'] as $cityId) {
                if ($cityId !== null) {
                    $zone->addCity($cityId);
                }
            }

            $this->serviceZoneRepo->save($zone);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'zonas de servicio');
    }

    // ── 8. Tickets ───────────────────────────────────────────────────────

    private function createTickets(): void
    {
        $this->io->writeln('  <info>createTickets started</info>');

        // Get clients in order
        $rucs = ['1790012344001', '1790098761001', '1790045676001'];
        $clients = [];
        foreach ($rucs as $ruc) {
            $c = $this->clientRepo->findByRuc(new ClientRucValueObject($ruc));
            if ($c !== null) {
                $clients[(string) $c->id()] = $c;
            }
        }
        if (empty($clients)) {
            return;
        }

        $adminEmpId = $this->existingEmployee(self::ADMIN_USERNAME);
        $carlosEmpId = $this->existingEmployee(self::TECH_USERNAME);
        $adminUserId = $this->userIdByUsername(self::ADMIN_USERNAME);

        $allAssignments = $this->terminalAssignmentRepo->findAll();
        if (empty($allAssignments)) {
            return;
        }

        $clientIds = array_keys($clients);
        $clientTerminals = [];
        $idx = 0;
        foreach ($allAssignments as $ta) {
            $assignGroup = $idx < 4 ? 0 : ($idx < 6 ? 1 : 2);
            $clientTerminalIdx = $clientIds[$assignGroup] ?? $clientIds[0];
            $clientTerminals[$clientTerminalIdx][] = $ta;
            $idx++;
        }
        if (empty($clientTerminals)) {
            return;
        }

        $firstClientTerminals = reset($clientTerminals);
        if ($firstClientTerminals === false) {
            $firstClientTerminals = [];
        }

        $ticketDefs = [
            ['client_idx' => 0, 'status' => 'assigned', 'type' => 'correctivo',  'desc' => 'Cajero no enciende en agencia Quito Centro', 'sla' => 4],
            ['client_idx' => 0, 'status' => 'in_assistance', 'type' => 'correctivo',  'desc' => 'Atasco de billetes en dispensador', 'sla' => 2],
            ['client_idx' => 0, 'status' => 'closed', 'type' => 'mantenimiento',  'desc' => 'Mantenimiento preventivo - limpieza general', 'sla' => 0],
            ['client_idx' => 0, 'status' => 'created', 'type' => 'correctivo',  'desc' => 'Pantalla táctil no responde correctamente', 'sla' => 4],
            ['client_idx' => 0, 'status' => 'closed', 'type' => 'mantenimiento',  'desc' => 'Actualización de software de seguridad', 'sla' => 0],
            ['client_idx' => 0, 'status' => 'cancelled', 'type' => 'correctivo', 'desc' => 'Falsa alarma - cliente canceló el servicio', 'sla' => 2],
            ['client_idx' => 0, 'status' => 'in_transit_back', 'type' => 'correctivo', 'desc' => 'Reparación de lector de tarjetas', 'sla' => 3],
            ['client_idx' => 1, 'status' => 'assigned', 'type' => 'correctivo',  'desc' => 'Cajero no dispensa billetes', 'sla' => 2],
            ['client_idx' => 1, 'status' => 'pending_closure', 'type' => 'correctivo', 'desc' => 'Módulo de depósito no funciona', 'sla' => 4],
            ['client_idx' => 1, 'status' => 'closed', 'type' => 'mantenimiento',  'desc' => 'Cambio de bobina térmica y limpieza', 'sla' => 0],
            ['client_idx' => 2, 'status' => 'waiting', 'type' => 'correctivo',  'desc' => 'Teclado EPP dañado - necesita reemplazo', 'sla' => 4],
            ['client_idx' => 2, 'status' => 'in_transit_out', 'type' => 'correctivo', 'desc' => 'Pantalla LCD con rayones', 'sla' => 3],
            ['client_idx' => 2, 'status' => 'closed', 'type' => 'correctivo',  'desc' => 'Reemplazo de ventilador de refrigeración', 'sla' => 2],
            ['client_idx' => 2, 'status' => 'cancelled', 'type' => 'correctivo', 'desc' => 'Cliente resolvió internamente', 'sla' => 0],
            ['client_idx' => 2, 'status' => 'closed', 'type' => 'mantenimiento',  'desc' => 'Mantenimiento trimestral programado', 'sla' => 0],
        ];

        $clientIdKeys = array_keys($clientTerminals);

        // Use native SQL inserts for ticket_assignments and ticket_findings
        // because Doctrine's dual mapping (field + insertable=false association) bricks the column
        $conn = $this->entityManager->getConnection();
        $ticketCount = 0;
        $findingCount = 0;
        $assignmentCount = 0;

        // First pass: persist all tickets via ORM
        $createdTicketData = [];
        foreach ($ticketDefs as $i => $def) {
            $ticketNumber = sprintf('TK-2026-%05d', $i + 2);
            if ($this->ticketRepo->findByTicketNumber($ticketNumber) !== null) {
                continue;
            }

            $clientId = $clientIdKeys[$def['client_idx']] ?? $clientIdKeys[0];

            $termAssignments = $clientTerminals[$clientId] ?? $firstClientTerminals;
            $termAssignment = $termAssignments[array_rand($termAssignments)];
            $termAssignmentId = (string) $termAssignment->id();

            $ticketId = new TicketIdValueObject($this->idGenerator->generate());
            $ticket = Ticket::create(
                id: $ticketId,
                ticketNumber: $ticketNumber,
                clientId: $clientId,
                terminalAssignmentId: $termAssignmentId,
                serviceType: ServiceType::from($def['type']),
                description: $def['desc'],
                createdBy: $adminUserId !== null ? (string) $adminUserId : 'unknown',
            );

            $targetStatus = TicketStatus::tryFrom($def['status']) ?? TicketStatus::CREATED;

            // Modify status via reflection
            try {
                $ref = new \ReflectionClass($ticket);

                $statusProp = $ref->getProperty('status');
                $statusProp->setAccessible(true);
                $statusProp->setValue($ticket, $targetStatus);

                $createDate = new DateTimeImmutable('2026-05-' . str_pad((string) random_int(1, 15), 2, '0', STR_PAD_LEFT) . ' 08:00:00');

                if ($targetStatus->value === 'closed' || $targetStatus->value === 'cancelled') {
                    $closedProp = $ref->getProperty('closedAt');
                    $closedProp->setAccessible(true);
                    $closedProp->setValue($ticket, $createDate->modify('+' . random_int(1, 5) . ' days'));
                }
            } catch (\ReflectionException) {
                // skip
            }

            $this->entityManager->persist($ticket);
            $ticketCount++;
            $createdTicketData[] = ['ticket' => $ticket, 'def' => $def, 'targetStatus' => $targetStatus];
        }

        // Flush tickets to DB so they get real IDs for FK constraints
        $this->entityManager->flush();

        // Second pass: create assignments and findings via native SQL
        foreach ($createdTicketData as $item) {
            $ticket = $item['ticket'];
            $def = $item['def'];
            $targetStatus = $item['targetStatus'];
            $ticketIdStr = (string) $ticket->id();

            if ($targetStatus === TicketStatus::ASSIGNED || $this->needsAssignment($targetStatus)) {
                $assignId = (string) new TicketAssignmentIdValueObject($this->idGenerator->generate());
                $empId = $carlosEmpId ?? $adminEmpId ?? 'unknown';
                $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
                $conn->executeStatement(
                    'INSERT INTO ticket_assignments (id, ticket_id, employee_id, is_in_charge, assigned_at) VALUES (:id, :tid, :eid, :iic, :aat)',
                    ['id' => $assignId, 'tid' => $ticketIdStr, 'eid' => $empId, 'iic' => 1, 'aat' => $now],
                );
                $assignmentCount++;
            }

            if ($targetStatus === TicketStatus::CLOSED) {
                $findingId = (string) new TicketFindingIdValueObject($this->idGenerator->generate());
                $problemType = match (random_int(0, 4)) {
                    0 => 'fallo_electrico', 1 => 'desgaste_mecanico', 2 => 'fallo_software', 3 => 'suciedad_excesiva', default => 'componente_danado'
                };
                $empId = $carlosEmpId ?? $adminEmpId ?? 'unknown';
                $conn->executeStatement(
                    'INSERT INTO ticket_findings (id, ticket_id, problem_type_found, problem_detail, battery_voltage_ok, solution, registered_by) VALUES (:id, :tid, :ptf, :pd, :bvo, :sol, :rb)',
                    ['id' => $findingId, 'tid' => $ticketIdStr, 'ptf' => $problemType, 'pd' => $def['desc'], 'bvo' => random_int(0, 1), 'sol' => 'Se realizó mantenimiento completo. Equipo operativo.', 'rb' => $empId],
                );
                $findingCount++;
            }
        }

        $this->ok($ticketCount, 'tickets');
        $this->ok($assignmentCount, 'asignaciones de tickets');
        $this->ok($findingCount, 'hallazgos');
    }

    private function needsAssignment(TicketStatus $s): bool
    {
        return in_array($s->value, ['assigned', 'in_assistance', 'in_transit_out', 'waiting', 'in_transit_back', 'pending_closure', 'closed'], true);
    }

    // ── 9. Repairs ───────────────────────────────────────────────────────

    private function createRepairs(): void
    {
        $labQuito = $this->locationByName('Laboratorio Técnico Quito');
        $labGuayaquil = $this->locationByName('Laboratorio Guayaquil');
        if ($labQuito === null && $labGuayaquil === null) {
            return;
        }

        $adminUserId = $this->userIdByUsername(self::ADMIN_USERNAME);
        $carlosUserId = $this->userIdByUsername(self::TECH_USERNAME);
        $mariaUser = $this->userRepo->findByUsername(new \App\Identity\Domain\ValueObjects\UsernameValueObject('maria'));
        $mariaUserId = $mariaUser !== null ? new UserIdValueObject((string) $mariaUser->id()) : null;

        // Get some serialized stock to repair
        $allSerialized = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
        $availableStock = array_filter($allSerialized, fn (SerializedStock $s) => $s->status()->value === 'available');
        $availableStock = array_values($availableStock);

        if (empty($availableStock)) {
            return;
        }
        $stockId = fn (int $i) => new SerializedStockIdValueObject((string) $availableStock[$i % count($availableStock)]->id());

        $repairsData = [
            ['lab' => 0, 'tech' => 'carlos', 'status' => 'completed', 'stock_idx' => 0, 'note' => 'Se reemplazó el sensor dañado', 'started' => '2026-05-10', 'completed' => '2026-05-11'],
            ['lab' => 0, 'tech' => 'carlos', 'status' => 'in_progress', 'stock_idx' => 1, 'note' => 'Diagnóstico inicial - posible corto', 'started' => '2026-05-22', 'completed' => null],
            ['lab' => 0, 'tech' => 'carlos', 'status' => 'pending', 'stock_idx' => 2, 'note' => 'Esperando repuesto', 'started' => null, 'completed' => null],
            ['lab' => 1, 'tech' => null, 'status' => 'pending', 'stock_idx' => 3, 'note' => 'Revisión de pantalla LCD', 'started' => null, 'completed' => null],
            ['lab' => 0, 'tech' => 'maria', 'status' => 'unrepairable', 'stock_idx' => 4, 'note' => 'Placa quemada - sin repuesto disponible', 'started' => '2026-05-15', 'completed' => '2026-05-16'],
        ];

        $labIds = [$labQuito, $labGuayaquil];
        $count = 0;

        foreach ($repairsData as $rd) {
            $labId = $labIds[$rd['lab']] ?? $labQuito;
            if ($labId === null) {
                continue;
            }

            $repair = Repair::assign(
                id: new RepairIdValueObject($this->idGenerator->generate()),
                serializedStockId: $stockId($rd['stock_idx']),
                laboratoryId: new LocationIdValueObject($labId),
                assignedById: $adminUserId ?? new UserIdValueObject($this->idGenerator->generate()),
                notes: $rd['note'],
            );

            // Set status via reflection
            try {
                $ref = new \ReflectionClass($repair);

                // Set status
                $statusProp = $ref->getProperty('status');
                $statusProp->setAccessible(true);
                $statusProp->setValue($repair, RepairStatus::from($rd['status']));

                // Set technician if any
                if ($rd['tech'] !== null) {
                    $techId = $rd['tech'] === 'carlos' ? $carlosUserId : $mariaUserId;
                    if ($techId !== null) {
                        $techProp = $ref->getProperty('technicianId');
                        $techProp->setAccessible(true);
                        $techProp->setValue($repair, $techId);
                    }
                }

                // Set dates
                if ($rd['started'] !== null) {
                    $startedProp = $ref->getProperty('startedAt');
                    $startedProp->setAccessible(true);
                    $startedProp->setValue($repair, new DateTimeImmutable($rd['started'] . ' 09:00:00'));
                }
                if ($rd['completed'] !== null) {
                    $completedProp = $ref->getProperty('completedAt');
                    $completedProp->setAccessible(true);
                    $completedProp->setValue($repair, new DateTimeImmutable($rd['completed'] . ' 17:00:00'));
                }

                // Mark stock as DAMAGED when repair is assigned
                $stock = $availableStock[$rd['stock_idx'] % count($availableStock)];
                if ($rd['status'] === 'in_progress' || $rd['status'] === 'completed' || $rd['status'] === 'unrepairable') {
                    try {
                        $stock->markDamaged();
                    } catch (\Throwable) {
                        // already damaged
                    }
                }
            } catch (\ReflectionException) {
                // skip
            }

            $this->repairRepo->save($repair);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'reparaciones');
    }

    // ── 10. Movements ────────────────────────────────────────────────────

    private function createMovements(): void
    {
        $bodegaCentral = $this->locationByName('Bodega Central Quito');
        $bodegaGuayaquil = $this->locationByName('Bodega Guayaquil');
        $bodegaCuenca = $this->locationByName('Bodega Cuenca');
        $mochilaCarlos = $this->locationByName('Mochila Carlos Técnico');
        $labQuito = $this->locationByName('Laboratorio Técnico Quito');

        if ($bodegaCentral === null) {
            return;
        }

        $adminEmpId = $this->existingEmployee(self::ADMIN_USERNAME);
        $carlosEmpId = $this->existingEmployee(self::TECH_USERNAME);

        // Get products
        $productInfrarrojo = $this->productByCode('SENS-IR-001');
        $productVentilador = $this->productByCode('VENT-80-001');
        $productCable = $this->productByCode('CABLE-USB-001');
        $productTeclado = $this->productByCode('TECL-ATM-001');
        $productBateria = $this->productByCode('BAT-LIT-001');

        $movementsData = [
            ['product' => $productInfrarrojo, 'type' => 'TRANSFER', 'source' => $bodegaCentral, 'dest' => $mochilaCarlos, 'ser' => true,  'qty' => 1, 'status' => 'COMPLETED', 'note' => 'Transferencia para ticket TK-2026-00002'],
            ['product' => $productVentilador, 'type' => 'TRANSFER', 'source' => $bodegaCentral, 'dest' => $mochilaCarlos, 'ser' => false, 'qty' => 3, 'status' => 'COMPLETED', 'note' => 'Reposición de ventiladores'],
            ['product' => $productCable,      'type' => 'TRANSFER', 'source' => $bodegaCentral, 'dest' => $bodegaGuayaquil, 'ser' => false, 'qty' => 10, 'status' => 'IN_TRANSIT', 'note' => 'Envío a bodega Guayaquil'],
            ['product' => $productTeclado,     'type' => 'TRANSFER', 'source' => $bodegaCentral, 'dest' => $labQuito, 'ser' => true,  'qty' => 1, 'status' => 'COMPLETED', 'note' => 'Envío a laboratorio para reparación'],
            ['product' => $productBateria,     'type' => 'TRANSFER', 'source' => $bodegaCentral, 'dest' => $bodegaCuenca, 'ser' => false, 'qty' => 20, 'status' => 'COMPLETED', 'note' => 'Reabastecimiento bodega Cuenca'],
        ];

        $count = 0;

        foreach ($movementsData as $md) {
            $product = $md['product'];
            if ($product === null) {
                continue;
            }
            $productId = (string) $product->id();

            $movement = Movement::create(
                id: new MovementIdValueObject($this->idGenerator->generate()),
                productId: new ProductIdValueObject($productId),
                type: MovementType::from(strtolower($md['type'])),
                sourceLocationId: $md['source'] !== null ? new LocationIdValueObject($md['source']) : null,
                destinationLocationId: $md['dest'] !== null ? new LocationIdValueObject($md['dest']) : null,
                serializedStockId: null,
                quantity: $md['qty'],
                notes: $md['note'],
                createdByUserId: $adminEmpId,
                assignedToUserId: $carlosEmpId,
            );

            // Set status
            try {
                $ref = new \ReflectionClass($movement);
                $statusProp = $ref->getProperty('status');
                $statusProp->setAccessible(true);
                $statusProp->setValue($movement, MovementStatus::from(strtolower($md['status'])));

                $createdProp = $ref->getProperty('createdAt');
                $createdProp->setAccessible(true);
                $createdProp->setValue($movement, new DateTimeImmutable('2026-05-' . str_pad((string) random_int(10, 20), 2, '0', STR_PAD_LEFT) . ' 10:00:00'));

                if ($md['status'] === 'COMPLETED') {
                    $completedProp = $ref->getProperty('completedAt');
                    $completedProp->setAccessible(true);
                    $completedProp->setValue($movement, new DateTimeImmutable('2026-05-' . str_pad((string) random_int(11, 21), 2, '0', STR_PAD_LEFT) . ' 14:00:00'));
                }
            } catch (\ReflectionException) {
                // skip
            }

            $this->movementRepo->save($movement);
            $count++;
        }
        $this->entityManager->flush();
        $this->ok($count, 'movimientos de stock');
    }

    // ── 11. Decommissioned Stock ──────────────────────────────────────────

    private function createDecommissionedStock(): void
    {
        $bodegaCentral = $this->locationByName('Bodega Central Quito');
        if ($bodegaCentral === null) {
            return;
        }

        // Mark some serialized as damaged
        $allSerialized = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(SerializedStock::class, 's')
            ->where('s.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
        $damagedCount = 0;
        $decommissionedCount = 0;

        foreach ($allSerialized as $i => $stock) {
            if ($stock->status()->value !== 'available') {
                continue;
            }

            if ($i % 5 === 0 && $damagedCount < 2) {
                try {
                    $stock->markDamaged();
                    $damagedCount++;
                } catch (\Throwable) {
                    // skip
                }
                continue;
            }

            if ($i % 7 === 0 && $decommissionedCount < 1) {
                try {
                    $stock->markDamaged();
                    $stock->decommission();
                    $decommissionedCount++;
                } catch (\Throwable) {
                    // skip
                }
            }
        }

        // Mark some non-serialized as damaged/decommissioned
        $allNonserialized = $this->entityManager->createQueryBuilder()
            ->select('ns')
            ->from(NonserializedStock::class, 'ns')
            ->where('ns.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
        $nsDamaged = 0;
        $nsDecommissioned = 0;

        foreach ($allNonserialized as $i => $stock) {
            if ($stock->status()->value !== 'available') {
                continue;
            }

            if ($i % 5 === 0 && $nsDamaged < 2) {
                try {
                    $stock->markDamaged();
                    $nsDamaged++;
                } catch (\Throwable) {
                    // skip
                }
                continue;
            }

            if ($i % 7 === 0 && $nsDecommissioned < 2) {
                try {
                    $stock->decommission();
                    $nsDecommissioned++;
                } catch (\Throwable) {
                    // skip
                }
            }
        }

        $this->entityManager->flush();
        $this->ok($damagedCount + $decommissionedCount, 'stock serializado dañado/baja');
        $this->ok($nsDamaged + $nsDecommissioned, 'stock no serializado dañado/baja');
    }

    // ── 12. Additional Expenses ───────────────────────────────────────────

    private function createAdditionalExpenses(): void
    {
        $carlosEmpId = $this->existingEmployee(self::TECH_USERNAME);
        $adminEmpId = $this->existingEmployee(self::ADMIN_USERNAME);
        $maria = $this->employeeRepo->findByEmail('maria@orbis.cms.com.ec');
        $mariaEmpId = $maria !== null ? (string) $maria->id() : null;

        if ($carlosEmpId === null && $mariaEmpId === null) {
            return;
        }

        // ── Operating expenses ──
        $oeData = [
            [
                'empId' => 'carlos',
                'reason' => 'Movilización para atención ticket TK-2026-00002',
                'start' => '2026-05-18', 'end' => '2026-05-18',
                'items' => [
                    ['type' => 'uber_empresarial', 'amount' => 12.50, 'notes' => 'Ida a agencia Quito Centro'],
                    ['type' => 'alimentacion', 'amount' => 8.00, 'notes' => 'Almuerzo'],
                    ['type' => 'uber_empresarial', 'amount' => 12.50, 'notes' => 'Vuelta a base'],
                ],
            ],
            [
                'empId' => 'carlos',
                'reason' => 'Movilización múltiple - revisión de 3 cajeros',
                'start' => '2026-05-20', 'end' => '2026-05-21',
                'items' => [
                    ['type' => 'taxi', 'amount' => 5.00, 'notes' => 'Taxi a agencia Norte'],
                    ['type' => 'alimentacion', 'amount' => 9.50, 'notes' => 'Desayuno y almuerzo'],
                    ['type' => 'bus_interprovincial', 'amount' => 15.00, 'notes' => 'Traslado a Sangolquí'],
                ],
            ],
            [
                'empId' => 'maria',
                'reason' => 'Supervisión de técnicos zona Guayaquil',
                'start' => '2026-05-22', 'end' => '2026-05-23',
                'items' => [
                    ['type' => 'hotel', 'amount' => 65.00, 'notes' => 'Hotel en Guayaquil 1 noche'],
                    ['type' => 'alimentacion', 'amount' => 18.00, 'notes' => 'Alimentación 2 días'],
                    ['type' => 'taxi', 'amount' => 8.00, 'notes' => 'Movilización local'],
                ],
            ],
        ];

        $oeCount = 0;
        $itemCount = 0;

        foreach ($oeData as $i => $oed) {
            $empId = $oed['empId'] === 'carlos' ? $carlosEmpId : $mariaEmpId;
            if ($empId === null) {
                continue;
            }

            $oeId = new OperatingExpenseIdValueObject($this->idGenerator->generate());
            if ($this->operatingExpenseRepo->findById($oeId) !== null) {
                continue;
            }

            $oe = OperatingExpense::create(
                id: $oeId,
                sequence: $i + 2,
                employeeId: $empId,
                reason: $oed['reason'],
                startDate: new DateTimeImmutable($oed['start']),
                endDate: new DateTimeImmutable($oed['end']),
            );

            // Submit to allow items
            try {
                $oe->submit();
            } catch (\Throwable) {
                // skip
            }

            $this->operatingExpenseRepo->save($oe);
            $oeCount++;

            // Create items
            foreach ($oed['items'] as $it) {
                $item = ExpenseItem::create(
                    id: new ExpenseItemIdValueObject($this->idGenerator->generate()),
                    type: ExpenseItemType::from($it['type']),
                    amount: $it['amount'],
                    notes: $it['notes'],
                );
                // Approve some, keep others pending
                if (random_int(0, 1) === 1) {
                    try {
                        $item->approve($adminEmpId ?? 'admin');
                    } catch (\Throwable) {
                        // skip
                    }
                }
                $oe->addItem($item);
                $itemCount++;
            }

            $this->operatingExpenseRepo->save($oe);
            $this->entityManager->flush();
        }

        $this->ok($oeCount, 'liquidaciones de gastos');
        $this->ok($itemCount, 'items de gasto');

        // ── Cash advances ──
        $caData = [
            ['empId' => 'carlos', 'amount' => 200.00,  'date' => '2026-05-15', 'number' => 'CA-2026-00002'],
            ['empId' => 'carlos', 'amount' => 50.00,   'date' => '2026-05-19', 'number' => 'CA-2026-00003'],
            ['empId' => 'maria',  'amount' => 150.00,  'date' => '2026-05-21', 'number' => 'CA-2026-00004'],
        ];

        $caCount = 0;
        foreach ($caData as $cad) {
            $empId = $cad['empId'] === 'carlos' ? $carlosEmpId : $mariaEmpId;
            if ($empId === null) {
                continue;
            }

            // Check by advance number to avoid unique constraint violation
            $existingCa = $this->cashAdvanceRepo->findAll();
            $caExists = false;
            foreach ($existingCa as $ea) {
                if ($ea->advanceNumber() === $cad['number']) {
                    $caExists = true;
                    break;
                }
            }
            if ($caExists) {
                continue;
            }

            $caId = new CashAdvanceIdValueObject($this->idGenerator->generate());
            $ca = CashAdvance::create(
                id: $caId,
                advanceNumber: $cad['number'],
                employeeId: $empId,
                givenBy: $adminEmpId ?? $empId,
                amount: $cad['amount'],
                givenAt: new DateTimeImmutable($cad['date']),
            );
            $this->entityManager->persist($ca);
            $caCount++;
        }
        $this->entityManager->flush();
        $this->ok($caCount, 'adelantos de efectivo');
    }
}
