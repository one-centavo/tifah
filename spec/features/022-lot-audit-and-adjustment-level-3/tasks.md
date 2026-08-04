# 022 · Lot Audit and Adjustment Level 3 — Tasks

## Database & Models Schema Updates
- [x] Create a migration adding `adjustment_reason` and `observations` columns to `inventory_movements` table.
- [x] Add them to `$fillable` in the `InventoryMovement` model.

## Business Logic (Service Layer)
- [x] Implement `adjustMovement` in `App\Services\LotService` inside a database transaction.
- [x] Calculate quantity difference from original movement, log the adjustment movement with parent link via `reference_id`, and update the lot's current quantity.

## Livewire Volt Component (Backend)
- [x] Implement `selectMovementForAdjustment(int $movementId)` state-binding method with administrator authorization check.
- [x] Update `saveAdjustment` validation and execution with admin checks.
- [x] Update movements list query sorting by `CASE WHEN type = 'adjustment' THEN reference_id ELSE id END` and then `id` ascending to keep adjustments grouped below their target movements.

## Blade Template (Frontend UI/UX)
- [x] Add the "Ajustar" button visible only for Administrators to each eligible log row.
- [x] Render Warehouse Assistants' visual blocker "Sólo Admin".
- [x] Update the modal to display context info of the specific movement being corrected.

## Testing & Verification
- [x] Create and implement feature tests in `tests/Feature/Pages/Inventory/LotLogsTest.php`.
- [x] Run the tests and verify they pass successfully.
