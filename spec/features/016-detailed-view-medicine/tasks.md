# 016 · Detailed View of Medicine — Tasks

- [ ] Add `withTrashed()` support to `viewDetails(int $id)` method to retrieve archived medicines.
- [ ] Update the Livewire component query to eager load all audit relations: `creator`, `updater`, `deleter`.
- [ ] Add the "Ver Detalle" button to archived medicine rows in the medicines index table.
- [ ] Design and layout the full detailed view in the modal `medicine-detail-modal`:
  - [ ] Render Commercial Name, Generic Name, Concentration, Presentation, Category, Sale Price, and Current Stock/Alert badge.
  - [ ] Render full Laboratory and INVIMA Sanitary Registry information (number, status, manufacturer, expiration).
  - [ ] Create description container displaying up to 500 characters of text.
  - [ ] Create barcode catalog list highlighting the main barcode.
  - [ ] Implement traceability panel displaying "Creado por", "Fecha de Creación", and "Última Modificación".
  - [ ] Display soft-delete details ("Eliminado por" and "Fecha de Eliminación") only if the medicine is archived.
  - [ ] Ensure all fields are strictly read-only.
  - [ ] Add action buttons: "Editar" and "Archivar" (conditioned to not-archived status).
- [ ] Write feature tests inside `MedicineManagementTest.php`:
  - [ ] Test that the detail modal loads all specifications and creator/updater audit fields.
  - [ ] Test that the detail modal correctly displays deletion audit info for soft-deleted medicines.
- [ ] Run test suite with `docker compose exec app php artisan test` to confirm everything passes.
- [ ] Format code using Pint and run PHPStan to ensure compliance.
