# 022 · Lot Audit and Adjustment Level 3 — Plan

## Approach

We will implement the Level 3 audit log at `/inventory/lots/{lot}/logs` (`inventory.lots.logs`) using a Livewire Volt component. We will update the database schema for inventory movements, extend the `LotService` to implement compensating adjustments on specific movements, and restrict access to corrections strictly to Administrator users.

---

### 1. Database & Schema Updates
- Create a migration to add `adjustment_reason` (string, 100, nullable) and `observations` (text, nullable) columns to the `inventory_movements` table.
- Update `App\Models\InventoryMovement` fillable list to include these fields.

---

### 2. Business Logic (`App\Services\LotService`)
- Implement `adjustMovement` in `LotService`:
  ```php
  public function adjustMovement(InventoryMovement $movement, int $newQuantity, string $reason, string $observations, int $userId): void
  ```
- The method will:
  - Wrap logic in a database transaction.
  - Calculate `quantityDiff = newQuantity - $movement->quantity`.
  - Create a new `InventoryMovement` record of type `'adjustment'`:
    - `lot_id` = `$movement->lot_id`
    - `type` = `'adjustment'`
    - `quantity` = `$quantityDiff`
    - `previous_balance` = `$movement->lot->current_quantity`
    - `new_balance` = `$movement->lot->current_quantity + $quantityDiff`
    - `concept` = `"Ajuste de cantidad del movimiento #{$movement->id}"`
    - `adjustment_reason` = `$reason`
    - `observations` = `$observations`
    - `reference_id` = `$movement->id`
    - `created_by` = `$userId`
  - Update the lot's `current_quantity` by adding `$quantityDiff`.

---

### 3. Livewire Volt Component (`resources/views/livewire/inventory/lot-logs.blade.php`)
- **State properties:**
  - `public Lot $lot;`
  - `public ?int $selectedMovementId = null;`
  - `public ?int $newQuantity = null;`
  - `public string $reason = '';`
  - `public string $observations = '';`
- **Actions:**
  - `selectMovementForAdjustment(int $movementId)`: Find the target movement, set state, abort if not admin.
  - `saveAdjustment(LotService $lotService)`:
    - Abort if not admin.
    - Validate inputs.
    - Call `$lotService->adjustMovement()`.
    - Reset states, close modal, flash success alert.
- **Sorting movements:**
  - Query lot movements, sorting them by:
    1. Parent ID: `CASE WHEN type = 'adjustment' THEN reference_id ELSE id END` ascending.
    2. ID: `id` ascending.
  - This groups adjustments directly below the movement they correct.

---

### 4. UI Layout & Styling
- Render the table of movements.
- Add an "Ajustar" button on each row where `type != 'adjustment'`, visible and enabled **only** for Administradores.
- Warehouse assistants will see "Sólo Admin" in place of actions and cannot adjust.

---

### 5. PestPHP Testing Strategy
- Access control verification.
- Movements display & sorting verify.
- Correcting a specific movement updates the lot stock and logs the adjustment compensating difference with the correct reason and observations.
- Warehouse assistant is forbidden from performing adjustments.
