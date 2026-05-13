# Conventions

## Estructura de Capas por Bounded Context

El proyecto aplica **DDD + Hexagonal Architecture** con **4 capas** por cada Bounded Context:

```
{BoundedContext}/
├── Domain/                    # Núcleo del dominio — CERO dependencias externas
│   ├── Entities/              # Entidades y Aggregate Roots
│   ├── ValueObjects/          # Value Objects (inmutables, readonly)
│   ├── Enums/                 # Enumeraciones del dominio
│   ├── Repositories/          # Interfaces de repositorios (puertos)
│   ├── Events/                # Domain Events
│   ├── Service/               # Domain Services
│   └── Exceptions/            # Excepciones de dominio
│
├── Application/               # Casos de uso — orquesta el dominio
│   ├── Command/               # Write operations (CQRS)
│   │   └── {ActionName}/
│   │       ├── {ActionName}Command.php
│   │       └── {ActionName}Handler.php
│   ├── Query/                 # Read operations (CQRS)
│   │   └── {QueryName}/
│   │       ├── {QueryName}Query.php
│   │       └── {QueryName}Handler.php
│   ├── Query/Response/        # DTOs de respuesta (UserResponse, RoleResponse…)
│   └── Exception/             # Excepciones de aplicación (UserNotFoundException…)
│
├── Infrastructure/            # Adaptadores secundarios (lado derecho del hexágono)
│   ├── Persistence/
│   │   └── Doctrine/
│   │       ├── Mapping/       # XML mappings de Doctrine (*.orm.xml)
│   │       └── Doctrine{Entity}Repository.php
│   ├── Doctrine/
│   │   └── Types/             # Custom DBAL types para Value Objects
│   └── Service/               # Implementaciones de servicios (NativePasswordHasher…)
│
└── Presentation/              # Adaptadores primarios (lado izquierdo del hexágono)
    └── Http/                  # Controllers HTTP (Web + API REST)
        └── {Resource}Controller.php
```

### Regla fundamental de capas
```
Presentation → Application → Domain ← Infrastructure
```
- **Domain**: no importa nada externo. Nunca.
- **Application**: importa Domain. No importa Infrastructure ni Presentation.
- **Infrastructure**: implementa interfaces de Domain. Importa Domain y librerías externas.
- **Presentation**: invoca Application (Commands/Queries). No accede a Domain directamente.

### Namespaces reales en el proyecto
```
App\{BC}\Domain\...
App\{BC}\Application\...
App\{BC}\Infrastructure\...
App\{BC}\Presentation\Http\...
```
Ejemplos:
- `App\Identity\Presentation\Http\UserController`
- `App\Organization\Presentation\Http\EmployeeController`
- `App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserRepository`
- `App\Identity\Infrastructure\Service\NativePasswordHasher`



## Nombrado

### Entidades
- Nombres en singular, PascalCase: `Ticket`, `SparePart`, `Employee`
- Aggregate Roots identificados explícitamente en la documentación

### Value Objects
- Sufijo `ValueObject`: `TicketIdValueObject`, `EmployeeEmailValueObject`
- Inmutables (propiedades `readonly`)
- Validación en constructor

### Enums
- Sufijo descriptivo si es necesario: `TicketStatusEnum`, `EmployeeStatus`
- Backed enums con `string` o `int`

### Repositories (Interface)
- Sufijo `RepositoryInterface`: `TicketRepositoryInterface`
- Ubicados en `Domain/Repository/`

### Repositories (Implementación)
- Prefijo `Doctrine`: `DoctrineTicketRepository`
- Ubicados en `Infrastructure/Persistence/Repository/`

### Commands y Queries
- Carpeta por caso de uso: `CreateTicket/`, `FindTicketById/`
- Clase Command/Query + Handler: `CreateTicketCommand.php`, `CreateTicketCommandHandler.php`

### Controllers
- Sufijo `Controller`: `UserController`, `EmployeeController`
- Ubicados en `{BC}/Presentation/Http/`
- Un controller por recurso/entidad principal del BC
- Métodos descriptivos: `list()`, `newForm()`, `create()`, `show()`, `edit()`, `update()`, `delete()`
- **NUNCA** acceder directamente al Domain ni a Infrastructure desde un controller
- Siempre invocar Application layer (Command/Query handlers)

### Excepciones
- Sufijo `Exception`: `TicketIdException`, `EmployeeEmailException`
- Extender de `DomainException` (Shared Kernel)

## Principio de Responsabilidad Única (SRP) en el Dominio

Cada entidad, Value Object o tabla de base de datos debe tener **una sola razón para cambiar**. Aplicado en la práctica:

- **No mezclar datos permanentes con datos temporales** en la misma entidad. Ejemplo: los tokens de activación/reset no viven en `User` — tienen su propia entidad `UserToken`.
- **No mezclar ciclos de vida distintos** en una tabla. Si un conjunto de campos solo aplica en ciertos estados o momentos, extraerlos a una entidad propia.
- **No mezclar lógica de dominio con lógica de coordinación**. La orquestación de múltiples BCs en un flujo (ej: crear User + Employee juntos) pertenece al Application Service, no a las entidades.
- **Señal de alerta:** Si una tabla tiene más de 2-3 columnas nullable que casi nunca se llenan al mismo tiempo, probablemente hay una entidad escondida que debe separarse.

```php
// ❌ MAL — User mezclado con responsabilidades de token
class User {
    private ?string $activationToken;
    private ?DateTimeImmutable $activationTokenExpiresAt;
    private ?string $passwordResetToken;
    private ?DateTimeImmutable $passwordResetTokenExpiresAt;
}

// ✅ BIEN — Responsabilidades separadas
class User {
    // Solo credenciales y estado de acceso
}

class UserToken {
    // Solo ciclo de vida de tokens temporales
}
```

## Shared Kernel

Código compartido entre Bounded Contexts ubicado en `src/app/Shared/`:

```
Shared/
├── Domain/
│   ├── ValueObject/
│   │   ├── AbstractUlid.php        # Base para todos los IDs de entidades
│   │   └── AbstractStringValueObject.php
│   ├── Exception/
│   │   └── DomainException.php     # Base de todas las excepciones de dominio
│   └── Event/
│       └── DomainEvent.php         # Base de todos los Domain Events
└── Infrastructure/
    └── Persistence/
        └── DoctrineRepository.php  # Base de implementaciones de repositorio
```

- `AbstractUlid` genera y valida ULIDs automáticamente
- Ningún Bounded Context debe depender directamente de otro; solo del Shared Kernel

## Identificadores (ULID)

- Todas las entidades usan **ULID** como identificador primario
- Los IDs se generan en el dominio (no en la base de datos)
- Formato: string(26), ordenable cronológicamente

```php
// ✅ Value Object de ID
final readonly class TicketId extends AbstractUlid {}

// ✅ Generar nuevo ID
$id = TicketId::generate();

// ✅ Reconstruir desde primitivo
$id = TicketId::fromString($request->get('id'));
```

## Soft Deletes

- Todas las entidades persistidas deben soportar soft deletes
- Campo estándar: `deleted_at` (nullable datetime)
- Implementar mediante la interfaz `SoftDeletableInterface` del Shared Kernel
- Los repositorios **nunca** retornan registros con `deleted_at` no nulo por defecto

```php
// ✅ Entidad con soft delete
final class Ticket implements SoftDeletableInterface
{
    private ?DateTimeImmutable $deletedAt = null;

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
```

## Reglas de Código

### Domain Layer
```php
// ✅ Value Object inmutable con validación
final readonly class TicketIdValueObject extends AbstractUlid
{
    // Hereda validación ULID del Shared Kernel
}

// ✅ Entidad con invariantes en constructor
final class Ticket
{
    private function __construct(
        private TicketIdValueObject $id,
        private ClientIdValueObject $clientId,
        private TicketNumberValueObject $ticketNumber,
        private TicketStatusEnum $status,
        // ...
    ) {
        // Validar invariantes
    }

    // Factory method para crear
    public static function create(...): self { }
    
    // Métodos de negocio
    public function startJourney(): void { }
    public function startAssistance(): void { }
}
```

### Application Layer
```php
// ✅ Command (DTO inmutable)
final readonly class CreateTicketCommand
{
    public function __construct(
        public string $clientId,
        public string $terminalHistoryId,
        public string $issueType,
        public string $issueDetail,
        public string $serviceType,
    ) {}
}

// ✅ Command Handler (caso de uso)
final readonly class CreateTicketCommandHandler
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepository,
    ) {}

    public function __invoke(CreateTicketCommand $command): void
    {
        // 1. Crear Value Objects desde primitivos
        // 2. Crear/obtener entidades
        // 3. Ejecutar lógica de dominio
        // 4. Persistir
    }
}
```

### Presentation Layer
```php
// ✅ Controller delgado, solo mapea HTTP → Application
#[Route('/api/v1/tickets', name: 'api_v1_tickets_')]
final class TicketController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $command = new CreateTicketCommand(
            clientId: $request->get('client_id'),
            // ...
        );
        
        ($this->createTicketHandler)($command);
        
        return new JsonResponse(['status' => 'created'], 201);
    }
}
```

## Autenticación JWT (API)

- Los endpoints de la API REST se protegen con JWT
- El token se envía en el header: `Authorization: Bearer {token}`
- Los controllers verifican autenticación mediante `#[IsGranted]` o un Guard personalizado
- Los tokens tienen expiración corta; se provee refresh token para renovar
- El frontend web usa sesión Symfony nativa (no JWT)

## Frontend — Convenciones

### Estructura de templates

```
templates/
├── base.html.twig           # Layout principal (sidebar + topbar + main)
├── {bounded_context}/       # Vistas por contexto (role/, client/, employee/, user/)
│   ├── list.html.twig       # Listado del recurso
│   ├── form.html.twig       # Formulario de creación/edición
│   └── show.html.twig       # Vista de detalle
```

### Patrón de formularios — OBLIGATORIO aplicar en todos los forms

Todo formulario de creación/edición debe seguir **exactamente** este patrón para garantizar consistencia visual:

**Wrapper y card:**
```twig
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
```

**Bloque de errores:**
```twig
{% if errors is not empty %}
    <div class="mb-5 bg-red-50 border border-red-200 rounded-lg p-4 space-y-1">
        {% for error in errors %}
            <p class="text-sm text-red-700">• {{ error }}</p>
        {% endfor %}
    </div>
{% endif %}
```

**Campos del formulario:**
```twig
<div class="space-y-5">
    <div>
        <label for="campo" class="block text-sm font-medium text-gray-700 mb-1">
            Etiqueta <span class="text-red-500">*</span>
        </label>
        <input type="text" id="campo" name="campo"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm outline-none"
               onfocus="this.style.borderColor='#1e3585'" onblur="this.style.borderColor=''"
               style="transition: border-color .15s;" required>
        <p class="mt-1 text-xs text-gray-400">Hint opcional.</p>
    </div>
</div>
```

**Divisor de secciones** (cuando el form tiene múltiples secciones lógicas):
```twig
<hr class="my-6 border-slate-100">
<p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Nombre sección</p>
```

**Botones de acción:**
```twig
<div class="mt-6 flex items-center gap-3">
    <button type="submit"
            class="text-white text-sm font-medium px-5 py-2.5 rounded-lg shadow-sm transition-all hover:shadow-md"
            style="background: #1e3585;" onmouseover="this.style.background='#152970'" onmouseout="this.style.background='#1e3585'">
        Acción
    </button>
    <a href="..." class="text-sm text-gray-500 hover:text-gray-700">
        Cancelar
    </a>
</div>
```

**Colores corporativos:**
- Primario: `#1e3585` (azul oscuro)
- Primario hover: `#152970`
- Acento: `#00a86b` (verde)

**Regla de consistencia:** Si se modifica el diseño de un formulario, se debe aplicar el mismo cambio a TODOS los demás formularios del sistema. La UI debe ser visualmente idéntica entre módulos.

### Responsive Design
- Mobile-first: los estilos base aplican a móvil, se amplían con `md:`, `lg:`, `xl:`
- Breakpoints estándar de Tailwind (640px, 768px, 1024px, 1280px)
- El sidebar colapsa a solo-iconos en pantallas pequeñas (`lg:` breakpoint)

## Respuestas de API

### Formato Estándar de Éxito
```json
{
    "data": { },
    "meta": {
        "timestamp": "2026-03-04T12:00:00Z"
    }
}
```

### Formato Estándar de Error
```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Los datos proporcionados no son válidos",
        "details": [
            {
                "field": "email",
                "message": "El email no es válido"
            }
        ]
    }
}
```

### Formato de Listado con Paginación
```json
{
    "data": [ ],
    "meta": {
        "current_page": 1,
        "per_page": 25,
        "total": 150,
        "last_page": 6,
        "timestamp": "2026-03-04T12:00:00Z"
    }
}
```

## Códigos HTTP

| Código | Uso                                          |
|--------|----------------------------------------------|
| 200    | Operación exitosa (GET, PUT, PATCH)          |
| 201    | Recurso creado (POST)                        |
| 204    | Operación exitosa sin contenido (DELETE)     |
| 400    | Request malformado                           |
| 401    | No autenticado                               |
| 403    | No autorizado (sin permisos)                 |
| 404    | Recurso no encontrado                        |
| 409    | Conflicto (duplicado, estado inválido)       |
| 422    | Error de validación                          |
| 500    | Error interno del servidor                   |

## Testing

### Estructura
```
tests/
├── Unit/
│   ├── {BoundedContext}/
│   │   └── Domain/
│   │       ├── Entity/
│   │       │   └── {Entity}Test.php
│   │       └── ValueObject/
│   │           └── {ValueObject}Test.php
│   └── Shared/
│
├── Integration/
│   └── {BoundedContext}/
│       ├── Application/
│       └── Infrastructure/
│
└── Functional/
    └── {BoundedContext}/
        └── Presentation/
            └── Controller/
```

### Convenciones de Tests
- Nombre del test: `test_{acción}_{escenario}_{resultado_esperado}`
- Ejemplo: `test_create_ticket_with_valid_data_should_succeed`
- Un archivo de test por clase del dominio

---

## Documentación del Código

### Reglas Generales
- **PHPDoc**: Documentar todas las clases, métodos y funciones con PHPDoc.
- **Ejemplos**: Incluir ejemplos de uso en los comentarios cuando sea necesario.
- **Parámetros y Retornos**: Describir claramente los parámetros, valores de retorno y excepciones.

### Herramientas
- Usar PHP-CS-Fixer para mantener un estilo de código consistente.
- Usar PHPStan o Psalm para análisis estático y detección temprana de errores.
