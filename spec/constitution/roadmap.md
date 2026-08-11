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
12. **026 · Delete Provider (HU 19)** — Permite archivar un proveedor mediante borrado suave, deshabilitándolo de las operaciones activas tras validar que no existan lotes de mercancía activos asociados, y registrando la auditoría de eliminación.
13. **027 · Register Customer (HU 20)** — Permite registrar y administrar la información de farmacias y clientes institucionales, aplicando el cálculo automático del DV de la DIAN, clasificación por ciudad, e integridad referencial de borrado suave condicionada a la ausencia de facturas asociadas.
14. **028 · Edit Client (HU 21)** — Permite modificar la información de clientes registrados, validando la unicidad de NIT en registros activos, recalculando en tiempo real el DV de la DIAN, preservando la auditoría original y reflejando cambios de forma inmediata.
15. **029 · Show and Filter Customers (HU 22)** — Permite visualizar, buscar, ordenar y filtrar los clientes registrados por su estado (activo/archivado) y ciudad, incluyendo acciones de edición y borrado suave.
16. **030 · Delete Customer (HU 23)** — Permite archivar un cliente mediante borrado suave desde el listado, validando la inexistencia de facturas asociadas en el histórico y registrando la auditoría de eliminación.

## Siguiente 🔜

_Lo próximo a abordar. Idealmente una sola feature "en curso" a la vez._

- **031 · Sales and Invoicing Process (HU 24)** — Permite el registro de ventas y facturación de salida seleccionando lotes específicos bajo rotación FEFO, descarga atómica de inventario, validaciones de crédito y generación de facturas en PDF.


## Backlog / ideas 💡

_Sin comprometer ni ordenar del todo. Ideas que respetan la constitución._

- **<Nombre>** — <qué aportaría>.


> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
