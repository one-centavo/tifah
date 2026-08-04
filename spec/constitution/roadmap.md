# Roadmap

_Orden y estado de las features. Es la vista de "qué hay hecho, qué toca ahora y qué viene". Cada entrada apunta a su carpeta en `features/`._

## Hecho ✅

_Features completadas, en orden de implementación._

1. **001 · Create Category** — Permite el registro de nuevas categorías de productos configurando restricciones logísticas por defecto.
2. **002 · Edit Category & Category Management** — Allows managing and editing medicine categories, soft deleting records, and displaying affected medicines.
3. **003 · Show and Filter Categories** — Provides comprehensive search, filter, and sort capabilities on the Category Management dashboard.
4. **004 · Delete Category** — Allows authorized users to soft delete medicine categories after verifying that no active medicines are associated with them.
5. **005 · Create Laboratory & Laboratory Management** — Permite registrar y administrar el catálogo de laboratorios fabricantes de medicamentos, aplicando borrado lógico y previniendo la eliminación si existen productos asociados.
6. **006 · Edit Laboratory** — Permite la edición de la información de laboratorios previamente registrados, garantizando la validación de unicidad en registros activos y la trazabilidad de las modificaciones.
7. **007 · Show and Filter Laboratories** — Permite visualizar, buscar, ordenar y filtrar los laboratorios registrados por su estado (activo/archivado), incluyendo las acciones de edición y eliminación suave.
8. **021 · Medicine Lots Level 2 (HU 28)** — Permite visualizar el listado detallado de todos los lotes activos de un medicamento específico, con alertas de vencimiento, ordenamiento y navegación al historial de logs.
9. **023 · Provider Registration (HU 16)** — Permite el registro y administración de distribuidores mayoristas y laboratorios, aplicando el cálculo automático del DV de la DIAN y restricciones de borrado suave.
10. **024 · Show and Filter Providers (HU 17)** — Permite visualizar, buscar, ordenar y filtrar los proveedores registrados por su estado (activo/archivado), incluyendo las acciones de edición y archivado bajo validación.
11. **025 · Edit Provider (HU 18)** — Permite la edición de la información de proveedores previamente registrados, aplicando validación de unicidad de NIT en registros activos, recálculo automático del DV de la DIAN y trazabilidad de las modificaciones.

## Siguiente 🔜

_Lo próximo a abordar. Idealmente una sola feature "en curso" a la vez._

- **<Nombre>** — <qué aportaría>.

## Backlog / ideas 💡

_Sin comprometer ni ordenar del todo. Ideas que respetan la constitución._

- **<Nombre>** — <qué aportaría>.


> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
