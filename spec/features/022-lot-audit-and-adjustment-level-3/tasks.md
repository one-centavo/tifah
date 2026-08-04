# 022 · Lot Audit and Adjustment Level 3 — Tasks

## Database & Models Schema Updates
- [x] Create a migration adding `adjusted_movement_id` foreign key to `inventory_movements` table.
- [x] Add `adjusted_movement_id` to `$fillable` in the `InventoryMovement` model.
- [x] Define the `adjustedMovement()` and `adjustments()` relationships on the `InventoryMovement` model.

## Business Logic (Service Layer)
- [x] Implement `adjustMovement` in `App\Services\LotService` inside a database transaction.
- [x] Calculate quantity difference from original movement, log the adjustment movement with parent link, and update the lot's current quantity.

## Livewire Volt Component (Backend)
- [x] Implement `selectMovementForAdjustment(int $movementId)` state-binding method.
- [x] Update `saveAdjustment` validation and execution using the new service method.
- [x] Update movements list query sorting by `adjusted_movement_id ?? id` and then `id` ascending to keep adjustments grouped below their target movements.

## Blade Template (Frontend UI/UX)
- [x] Add the "Ajustar" button to each eligible log row (non-adjustment rows).
- [x] Update the modal to display context info of the specific movement being corrected.

## Testing & Verification
- [x] Create and implement feature tests in `tests/Feature/Pages/Inventory/LotLogsTest.php`.
- [x] Run the tests and verify they pass successfully.
