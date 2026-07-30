# Roadmap

_Orden y estado de las features. Es la vista de "qué hay hecho, qué toca ahora y qué viene". Cada entrada apunta a su carpeta en `features/`._

## Hecho ✅

_Features completadas, en orden de implementación._

1. **001 · Create Category** — Permite el registro de nuevas categorías de productos configurando restricciones logísticas por defecto.
2. **002 · Edit Category & Category Management** — Allows managing and editing medicine categories, soft deleting records, and displaying affected medicines.
3. **003 · Show and Filter Categories** — Provides comprehensive search, filter, and sort capabilities on the Category Management dashboard.
4. **004 · Delete Category** — Allows authorized users to soft delete medicine categories after verifying that no active medicines are associated with them.

## Siguiente 🔜

_Lo próximo a abordar. Idealmente una sola feature "en curso" a la vez._

1. **005 · Create Laboratory & Laboratory Management** — Permite registrar y administrar el catálogo de laboratorios fabricantes de medicamentos, aplicando borrado lógico y previniendo la eliminación si existen productos asociados.

## Backlog / ideas 💡

_Sin comprometer ni ordenar del todo. Ideas que respetan la constitución._

- **<Nombre>** — <qué aportaría>.
- **<Nombre>** — <qué aportaría>.

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
