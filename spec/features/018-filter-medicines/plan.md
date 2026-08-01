# 018 · Advanced Filters for Medicines — Plan

## Approach

We will enhance the existing medicines index Livewire component (`resources/views/livewire/medicines/index.blade.php`) to introduce new state variables for advanced filters, update the query building logic to apply these filters dynamically, layout the new filter UI controls, and write comprehensive Pest feature tests to verify their behaviors.

### 1. Component State & Hooks (`App\Livewire\Medicines\Index`)
We will add the following properties to the component class in the Volt file:
- `$laboratoryFilter = 'all';` (string containing laboratory ID or `'all'`)
- `$coldChainFilter = 'all';` (options: `'all'`, `'yes'`, `'no'`)
- `$specialControlFilter = 'all';` (options: `'all'`, `'yes'`, `'no'`)
- `$stockAlertFilter = 'all';` (options: `'all'`, `'low'`, `'out'`)

To preserve proper pagination behavior, we will add lifecycle hooks to reset the page when any of these filters change:
```php
public function updatingLaboratoryFilter(): void
{
    $this->resetPage();
}

public function updatingColdChainFilter(): void
{
    $this->resetPage();
}

public function updatingSpecialControlFilter(): void
{
    $this->resetPage();
}

public function updatingStockAlertFilter(): void
{
    $this->resetPage();
}
```

### 2. Query Modification in `with()` Method
We will modify the query builder for `Medicine` in the `with()` method:
- **Laboratory Filter:**
  ```php
  if ($this->laboratoryFilter !== 'all') {
      $query->where('laboratory_id', $this->laboratoryFilter);
  }
  ```
- **Cold Chain Filter:**
  ```php
  if ($this->coldChainFilter === 'yes') {
      $query->where('is_cold_chain', true);
  } elseif ($this->coldChainFilter === 'no') {
      $query->where('is_cold_chain', false);
  }
  ```
- **Special Control Filter:**
  ```php
  if ($this->specialControlFilter === 'yes') {
      $query->where('is_special_control', true);
  } elseif ($this->specialControlFilter === 'no') {
      $query->where('is_special_control', false);
  }
  ```
- **Stock Alert Filter:**
  To evaluate the sum of stock dynamically per medicine inside the query without triggering N+1 issues or breaking pagination counting, we will use SQL subqueries:
  - **Low Stock (`'low'`):** Actual stock is less than or equal to minimum stock.
    ```php
    if ($this->stockAlertFilter === 'low') {
        $query->whereRaw('(SELECT COALESCE(SUM(current_quantity), 0) FROM lots WHERE lots.medicine_id = medicines.id AND lots.deleted_at IS NULL) <= medicines.min_stock');
    }
    ```
  - **Out of Stock (`'out'`):** Actual stock is exactly zero.
    ```php
    if ($this->stockAlertFilter === 'out') {
        $query->whereRaw('(SELECT COALESCE(SUM(current_quantity), 0) FROM lots WHERE lots.medicine_id = medicines.id AND lots.deleted_at IS NULL) = 0');
    }
    ```

### 3. Fetching Master Data
- We will retrieve all registered laboratories in the `with()` method to populate the laboratory filter dropdown:
  ```php
  'laboratories' => Laboratory::orderBy('name')->get(),
  ```

### 4. UI Layout Upgrades
We will expand the "Filtrar Medicamentos" card:
- Adjust the layout grid to fit the new controls responsively (e.g., using `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5`).
- Add the **Laboratorio** select dropdown.
- Add the **Cadena de Frío** select dropdown / toggle selector.
- Add the **Control Especial** select dropdown / toggle selector.
- Add the **Alerta de Inventario** select dropdown or radio buttons group.
- Verify the empty state container rendering: it already checks `$medicines->isEmpty()` and outputs `"No se encontraron medicamentos que coincidan con los filtros aplicados"`, which matches the empty state requirement perfectly.

### 5. Test Suite Expansion (`tests/Feature/Pages/Medicines/MedicineManagementTest.php`)
We will add test scenarios to cover all new filters:
- Filter by laboratory ID.
- Filter by cold chain (Yes / No).
- Filter by special control (Yes / No).
- Filter by stock status:
  - Low Stock (with lots having stock <= min_stock).
  - Out of stock (with lots having stock = 0 or no lots).
- Filter combinations (e.g. Laboratory X AND Cold Chain Yes AND Low Stock).
- Assert the empty state is displayed when combinations yield no matches.
