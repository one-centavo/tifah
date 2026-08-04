# 022 · Lot Audit and Adjustment Level 3 — Plan

## Approach

We will implement the Level 3 audit log at `/inventory/lots/{lot}/logs` (`inventory.lots.logs`) using a Livewire Volt component. We will update the database schema for inventory movements, extend the `LotService` to implement compensating adjustments on specific movements, and update the UI to allow initiating corrections from individual log rows.

---

### 1. Database & Schema Updates
- Create a migration to add an `adjusted_movement_id` nullable foreign key to `inventory_movements` table referencing `inventory_movements(id)`.
- Update `App\Models\InventoryMovement` to include `'adjusted_movement_id'` in `$fillable` (if fillable is used) and define the relationship:
  ```php
  public function adjustedMovement()
  {
      return $this->belongsTo(InventoryMovement::class, 'adjusted_movement_id');
  }

  public function adjustments()
  {
      return $this->hasMany(InventoryMovement::class, 'adjusted_movement_id');
  }
  ```

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
    - `adjusted_movement_id` = `$movement->id`
    - `type` = `'adjustment'`
    - `quantity` = `$quantityDiff`
    - `previous_balance` = `$movement->lot->current_quantity`
    - `new_balance` = `$movement->lot->current_quantity + $quantityDiff`
    - `concept` = `"Ajuste de cantidad del movimiento #{$movement->id} por: " . $reason . " - " . $observations`
    - `reference_id` = `$movement->reference_id`
    - `created_by` = `$userId`
  - Update the lot's `current_quantity` by adding `$quantityDiff`.

---

### 3. Livewire Volt Component (`resources/views/livewire/inventory/lot-logs.blade.php`)
- **State properties:**
  - `public Lot $lot;`
  - `public ?InventoryMovement $selectedMovement = null;`
  - `public ?int $newQuantity = null;`
  - `public string $reason = '';`
  - `public string $observations = '';`
  - `public string $adminPassword = '';`
- **Actions:**
  - `selectMovementForAdjustment(int $movementId)`: Find the target movement, set `$selectedMovement`, default `$newQuantity` to its current quantity, and open the modal.
  - `saveAdjustment(LotService $lotService)`:
    - Validate inputs.
    - If user is warehouse assistant, verify `adminPassword` against an administrator's password.
    - Call `$lotService->adjustMovement()`.
    - Reset states, close modal, flash success alert.
- **Sorting movements:**
  - Query lot movements, sorting them by:
    1. Parent ID: `adjusted_movement_id ?? id` ascending.
    2. ID: `id` ascending.
  - This groups adjustments directly below the movement they correct.

---

### 4. UI Layout & Styling
- Render the table of movements.
- Add an "Ajustar" button on each row where `type != 'adjustment'` and `adjusted_movement_id` is null.
- Inside the modal, display the original movement details: original date, original quantity, and who performed it, alongside fields for the correction.

---

### 5. PestPHP Testing Strategy
- Access control verification.
- Movements display & sorting verify.
- Correcting a specific movement updates the lot stock and logs the adjustment compensating difference.
- Warehouse assistant validation checks.
