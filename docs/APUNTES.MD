# Decisiones técnicas y notas de desarrollo

> Registro de decisiones de diseño, pendientes técnicos y notas de implementación.
> Agregar aquí cualquier decisión que no encaje en los docs de arquitectura o convenciones.

---

## Decisiones de diseño

### DTOs de respuesta — proyección parcial de campos
Los DTOs de respuesta (`*Response`) deben exponer **solo los campos necesarios** para la vista o cliente que los consume. Evitar devolver todos los campos del aggregate — esto reduce la sobrecarga de datos en las respuestas y protege información sensible.

### Paginación obligatoria en listados
Todos los listados (users, employees, clients, contacts, roles, tickets, etc.) deben usar paginación tanto en el backend (query con limit/offset) como en los templates (controles prev/next + info de página actual).

---

## Pendientes técnicos

*(Agregar aquí issues técnicos detectados durante el desarrollo que no son tareas del roadmap)*
