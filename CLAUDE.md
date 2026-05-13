# Proyecto
Orbis

## Descripción

**Orbis** sistema web fullstack tipo ERP seguro y altamente escalable para la gestión integral y el control de la operación empresarial en Automated Cash Management Solutions S.A.

El sistema es una reescritura completa del proyecto **CMS-Control-Suite** hacia una arquitectura moderna basada en **Domain-Driven Design (DDD)** y **Hexagonal Architecture**. 

-   Ruta CMS Control Suite: 
    C:\Users\Jael Parrales\OneDrive - Automated Cash Management Solutions SA\Documentos\www\CMS-Control-Suite

Nota: se espera a futuro desarrollar una aplicación móvil, por tal razón el API REST debe desarrollarse en paralelo.

## Objetivo 

Construir una aplicación WEB y exponer una API REST que permita:

- Gestionar empleados y clientes
- Gestionar usuarios, roles y permisos que pueden otorgarse a empleados y clientes (opcional)
- Gestionar contratos, condiciones y ubicaciones de servicio ofrecido a los clientes
- Gestionar órdenes de compra de cajeros automáticos de clientes
- Gestionar ciclo de vida de tickets de servicio técnico de cajeros automáticos
- Gestionar gastos de la operación de la empresa relacionada a la resolución de incidencias de cajeros reportadas
- Gestionar el inventario de repuestos, almacenes y laboratorios
- Gestionar ciclo de vida de repuestos de cajeros automáticos
- Gestionar ciclo de vida de reparación de repuestos de cajeros automáticos
- Gestionar órdenes de compra de repuestos de cajeros automáticos

## Stack Tecnológico

| Componente       | Tecnología              |
|------------------|-------------------------|
| Backend          | Symfony 8.0 / PHP 8.4+  |
| Frontend         | Turbo de Symfony        |
|                  | TailwindCSS             |
| Base de Datos    | MySQL                   |
| Autenticación    | JWT (API for mobile)    |
| Documentación API| Markdown                |
| Testing          | PHPUnit 11              |

## Principios de Diseño

El sistema debe estar construido sobre bases sólidas de programación para un diseño escalable y mantenible

1. **Domain-Driven Design (DDD)**: Bounded Contexts, Aggregates, Value Objects, Domain Events
2. **Hexagonal Architecture**: Separación estricta entre Domain, Application e Infrastructure
3. **SOLID Principles**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
4. **CQRS Ligero**: Separación de comandos (escritura) y queries (lectura) donde tenga sentido
5. **Test-Driven Approach**: Entidades y Value Objects con cobertura de tests unitarios
6. **API-First**: Toda funcionalidad se expone como API REST para ser consumida por un cliente móvil
7. **Pattern-design**: uso de patrones de diseño para el back y front (como referencia consultar https://refactoring.guru/es)
8. **Database**: tablas relaciones optimizadas y uso de softdeletes 
9. **Responsive design**: la UI debe ser completamente adaptable a cualquier tamaño de pantalla de dispositivo

## Sistema de Origen (CMS-Control-Suite)

El sistema existente fue construido con:
- **Laravel** como framework PHP
- **MySQL** como base de datos
- **Filament** como panel de administración (frontend integrado)
- **Spatie Permission** para roles y permisos
- **Livewire** para interactividad
- **UUID** como identificadores



