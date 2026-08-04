# 022 · Lot Audit and Adjustment Level 3 — Tasks

## Database & Models Schema Updates
- [ ] Create a migration adding `adjusted_movement_id` foreign key to `inventory_movements` table.
- [ ] Add `adjusted_movement_id` to `$fillable` in the `InventoryMovement` model.
- [ ] Define the `adjustedMovement()` and `adjustments()` relationships on the `InventoryMovement` model.

## Business Logic (Service Layer)
- [ ] Implement `adjustMovement` in `App\Services\LotService` inside a database transaction.
- [ ] Calculate quantity difference from original movement, log the adjustment movement with parent link, and update the lot's current quantity.

## Livewire Volt Component (Backend)
- [ ] Implement `selectMovementForAdjustment(int $movementId)` state-binding method.
- [ ] Update `saveAdjustment` validation and execution using the new service method.
- [ ] Update movements list query sorting by `adjusted_movement_id ?? id` and then `id` ascending to keep adjustments grouped below their target movements.

## Blade Template (Frontend UI/UX)
- [ ] Add the "Ajustar" button to each eligible log row (non-adjustment rows).
- [ ] Update the modal to display context info of the specific movement being corrected.

## Testing & Verification
- [ ] Create and implement feature tests in `tests/Feature/Pages/Inventory/LotLogsTest.php`.
- [ ] Run the tests and verify they pass successfully.
