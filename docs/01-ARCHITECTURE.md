# Arquitectura del Proyecto — Orbis

## Stack Tecnológico

| Componente | Tecnología |
|---|---|
| Backend | PHP 8.4+ / Symfony 8.0 |
| ORM | Doctrine ORM con XML mappings (sin anotaciones) |
| Frontend | Twig + Symfony UX Turbo + TailwindCSS |
| Base de datos | MySQL 8+ |
| Testing | PHPUnit 11 |
| Auth web | Symfony Security (sesión HTTP) |
| Auth API | JWT (`lexik/jwt-authentication-bundle`) |
| MFA | TOTP (`spomky-labs/otphp`) |
| Identificadores | ULID generado en dominio (nunca auto-increment) |

---

## Arquitectura — DDD + Hexagonal

El sistema se divide en **Bounded Contexts (BCs)**. Cada BC tiene exactamente 4 capas:

```
src/app/{BoundedContext}/
├── Domain/                      # Núcleo — cero dependencias externas
│   ├── Entities/                # Entities y Aggregate Roots
│   ├── ValueObjects/            # Value Objects (readonly, inmutables)
│   ├── Enums/                   # Backed enums del dominio
│   ├── Repositories/            # Interfaces de repositorio (puertos)
│   ├── Service/                 # Domain Services
│   ├── Events/                  # Domain Events
│   └── Exceptions/              # Excepciones de dominio
│
├── Application/                 # Casos de uso — orquesta el dominio
│   ├── Command/{ActionName}/    # Write: {Action}Command.php + {Action}Handler.php
│   ├── Query/{QueryName}/       # Read: {Query}Query.php + {Query}Handler.php
│   ├── Query/Response/          # DTOs de respuesta (UserResponse, RoleResponse…)
│   └── Exception/               # Excepciones de aplicación (NotFoundException…)
│
├── Infrastructure/              # Adaptadores secundarios
│   ├── Persistence/Doctrine/
│   │   ├── Mapping/             # XML mappings (*.orm.xml)
│   │   └── Doctrine{Entity}Repository.php
│   ├── Doctrine/Types/          # Custom DBAL types para Value Objects
│   └── Service/                 # Implementaciones de servicios
│
└── Presentation/Http/           # Adaptadores primarios (Controllers)
    └── {Resource}Controller.php
```

### Regla fundamental de capas

```
Presentation → Application → Domain ← Infrastructure
```

- **Domain**: no importa nada externo. Nunca.
- **Application**: importa Domain únicamente.
- **Infrastructure**: implementa interfaces de Domain.
- **Presentation**: invoca Application (Commands/Queries). No accede al Domain directamente.

### Namespaces reales

```
App\{BC}\Domain\...
App\{BC}\Application\...
App\{BC}\Infrastructure\...
App\{BC}\Presentation\Http\...
```

---

## Shared Kernel

Código compartido entre todos los BCs. Ubicado en `src/app/Shared/`:

```
Shared/
├── Domain/
│   ├── ValueObject/
│   │   ├── AbstractUlid.php              # Base para todos los IDs
│   │   └── AbstractStringValueObject.php
│   └── Exception/
│       └── DomainException.php           # Base de excepciones de dominio
└── Infrastructure/
    └── Persistence/Doctrine/
        └── Types/
            ├── AbstractUlidType.php       # Doctrine type base para IDs ULID
            ├── AbstractStringValueObjectType.php   # Doctrine type base para VOs string
            └── AbstractStringEnumType.php # Doctrine type base para enums
```

---

## Patrones de Código

### Domain — Value Object

```php
final readonly class TicketIdValueObject extends AbstractUlid {}

$id = TicketIdValueObject::generate();           // Nuevo ULID
$id = TicketIdValueObject::fromString($string);  // Desde primitivo
```

### Domain — Aggregate Root

```php
final class Ticket
{
    private function __construct(
        private readonly TicketIdValueObject $id,
        private TicketStatus $status,
    ) {}

    public static function open(TicketIdValueObject $id, ...): self
    {
        return new self($id, TicketStatus::OPEN, ...);
    }

    public function close(): void
    {
        if ($this->status !== TicketStatus::OPEN) {
            throw new DomainException('Only open tickets can be closed');
        }
        $this->status = TicketStatus::CLOSED;
    }
}
```

### Application — Command Handler

```php
final readonly class CreateTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private ClientRepositoryInterface $clients,
    ) {}

    public function __invoke(CreateTicketCommand $command): void
    {
        $clientId = ClientIdValueObject::fromString($command->clientId);
        $client = $this->clients->findById($clientId)
            ?? throw ClientNotFoundException::withId($command->clientId);

        $ticket = Ticket::open(TicketIdValueObject::generate(), $clientId, ...);
        $this->tickets->save($ticket);
    }
}
```

### Presentation — Controller

```php
#[Route('/tickets', name: 'ticket_')]
final class TicketController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ListTicketsHandler $handler): Response
    {
        return $this->render('ticket/list.html.twig', [
            'tickets' => $handler(new ListTicketsQuery()),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, CreateTicketHandler $handler): Response
    {
        try {
            $handler(new CreateTicketCommand(clientId: $request->request->get('client_id'), ...));
            $this->addFlash('success', 'Ticket creado.');
            return $this->redirectToRoute('ticket_list');
        } catch (DomainException|ApplicationException $e) {
            return $this->render('ticket/form.html.twig', ['errors' => [$e->getMessage()]], new Response(status: 422));
        }
    }
}
```

---

## Doctrine ORM — Reglas

- **Solo XML mappings** — nunca anotaciones ni atributos PHP en entidades
- XML files: `Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml`
- Custom types registrados en `config/packages/doctrine.yaml`
- `deleted_at` en todas las entidades persistidas (excepto auditoría inmutable y tablas pivot)
- `created_at` / `updated_at` gestionados por `TimestampableTrait` en infraestructura — **el dominio no los conoce**

---

## API REST

- Base path: `/api/v1/`
- Auth header: `Authorization: Bearer {jwt}`
- Frontend web: sesión HTTP de Symfony (no JWT)
- Paginación obligatoria en todos los listados
- Respuesta de error: `{"error": {"code": "...", "message": "..."}}`

---

## Tests

```
tests/
├── Unit/
│   └── {BoundedContext}/
│       ├── Domain/
│       │   ├── Entity/          # Invariantes de entidades
│       │   └── ValueObject/     # Validación de VOs
│       └── Application/
│           └── Command/         # Handlers con mocks de repositorios
└── Integration/                 # (futuro) Tests con BD real
```

Cobertura obligatoria: todos los Value Objects y Command Handlers.
Nunca instanciar Doctrine ni BD en tests unitarios — usar mocks.
- Usar Value Objects para:
  - IDs
  - Emails
  - Estados
- Validar invariantes en constructor o métodos

Ejemplo:

```php
final class Ticket
{
    public function assign(UserId $userId): void
    {
        if ($this->isClosed()) {
            throw new DomainException('Cannot assign closed ticket');
        }

        $this->assignedTo = $userId;
    }
}

## Identity BC Updates

### Differences Identified
1. **User Assignment**:
   - Previously missing relationships with `Employee` and `Contact`.
   - Validation for exclusive assignment added.

2. **Cascade Deactivation**:
   - Implemented to ensure users deactivate along with their assigned entities.

3. **Assignment Validation**:
   - Users must now be assigned to either an `Employee` or `Contact`.

### Next Steps
- Validate integration with `Organization BC` and `Client BC`.
- Ensure cross-BC consistency for `UserEmailResolverInterface` and `UserBindingCheckerInterface`.
```