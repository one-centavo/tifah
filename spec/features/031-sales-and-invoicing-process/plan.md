# 031 · Sales and Invoicing Process — Plan

## Approach

Implement the Sales and Invoicing Process feature following the domain-driven layered architecture: a robust Service layer (`BillService` / `SaleService`), transactional DB operations with pessimistic locking for concurrency safety, automated FEFO allocation logic, Livewire Volt reactive components for POS-style fast input, and PDF invoice rendering.

---

### 1. Database & Schema Enhancements

- **`bills` Table & `Bill` Model**:
  - Add fields if missing: `invoice_number`, `payment_method` (`cash`, `transfer`, `credit`), `payment_due_date` (nullable date), `total_amount` (integer/decimal), `annulled_reason` (nullable string), `annulled_by` (nullable foreignId to users), `annulled_at` (nullable timestamp).
  - Status enum: `draft`, `active`, `annulled`.
  - Relations: `customer()`, `creator()`, `updater()`, `annuller()`, `details()`, `inventoryMovements()`.

- **`bill_details` Table & `BillDetail` Model**:
  - Fields: `bill_id`, `lot_id`, `quantity`, `unit_price`, `subtotal`.
  - Relations: `bill()`, `lot()`.

- **`lots` & `InventoryMovement`**:
  - `Lot` model: decrement/increment operations on `current_quantity`.
  - `InventoryMovement` model: track movements of type `sale` (deduction) and `annulment_return` (restitution).

- **`customers` Table**:
  - Ensure `credit_limit` and current balance are queryable.

---

### 2. Service Layer (`App\Services\BillService`)

- **`allocateFefoLots(Medicine $medicine, int $requestedQuantity, array $lockedAssignments = []): array`**:
  - Fetches non-expired, available lots (`current_quantity > 0` and `expiration_date >= today`) ordered by `expiration_date ASC`.
  - Honors manually locked lot assignments without overriding them.
  - Distributes the remaining requested quantity sequentially across the earliest expiring lots.
  - Returns allocation breakdown with warnings if total available stock < requested quantity.

- **`validateCreditEligibility(Customer $customer, float $saleAmount): bool`**:
  - Calculates existing unpaid/credit bills for the customer.
  - Verifies that `(unpaid_credit_sum + saleAmount) <= customer->credit_limit`. Throws `ValidationException` if exceeded.

- **`createSale(array $billData, array $items, int $userId): Bill`**:
  - Wrapped inside `DB::transaction()`.
  - Uses `Lot::whereIn('id', $lotIds)->lockForUpdate()->get()` to lock rows against concurrent updates.
  - Validates that each lot has sufficient `current_quantity >= item.quantity`.
  - Computes subtotal and rounds total to nearest integer peso (`round($total, 0)`).
  - Creates the `Bill` record with status `active`.
  - Creates `BillDetail` records.
  - Decrements each lot's `current_quantity`.
  - Creates `InventoryMovement` entries (`type = 'sale'`).

- **`annulBill(Bill $bill, string $reason, int $userId): Bill`**:
  - Wrapped inside `DB::transaction()`.
  - Validates that bill is currently `active` (cannot annul already annulled bills).
  - Iterates over `$bill->details`: restores quantity to each lot (`lot->current_quantity += detail->quantity`).
  - Creates compensating `InventoryMovement` entries (`type = 'annulment_return'`).
  - Updates bill status to `annulled`, setting `annulled_reason`, `annulled_by = $userId`, and `annulled_at = now()`.

---

### 3. Livewire Volt Components & UI

- **Sales Creation Screen (`resources/views/livewire/sales/create.blade.php`)**:
  - **Customer Search / Autocomplete**: Input with reactive search for NIT or Razón Social; loads customer details and credit limits.
  - **Barcode Scanner / Product Search Input**: Auto-focus on mount; listener for `Enter` key barcode scan or manual typing.
  - **Medicine Quick Registration Modal**: Triggered when product not found; allows registering medicine without losing cart state.
  - **FEFO Lot Suggestion Panel**: Visual table displaying available batches with expiration badges (color-coded) and auto-allocated quantities.
  - **Manual Lot Adjustment & Lock Toggle**: Checkbox or padlock icon to freeze user-assigned batch selections.
  - **Cart Table**: Live table of selected lines showing Product, Batch Number, Expiry Date, Quantity, Unit Price (editable), Subtotal, and Remove action.
  - **Payment & Totals Bar**: Radio/select for Cash, Transfer, Credit. If Credit is selected, displays Due Date datepicker and credit limit indicator. Grand total with integer rounding.
  - **Pre-Submit Completeness Validation**: Alerts if requested total does not match assigned batch sum, offering "Autocompletar Lotes Faltantes".
  - **Submit Button**: Triggers `BillService->createSale()` and opens confirmation modal with PDF download/print options.

- **Bill Management & Detail Views (`resources/views/livewire/bills/index.blade.php` & `show.blade.php`)**:
  - Listing table with invoice number, customer name, total amount, payment method, emission date, status badge (`active`, `annulled`).
  - Search and filters (by customer, date range, payment method, status).
  - Invoice detail modal/page with breakdown of lots, prices, and audit metadata.
  - "Anular Factura" action button prompting for annulment justification.

---

### 4. PDF Invoice Generation

- Controller/Route: `GET /bills/{bill}/pdf` (`BillPdfController@download` or `@stream`).
- Standardized Blade view formatted for printing/receipt:
  - Header: Distributor Legal Name, NIT, Address, Contact Info.
  - Invoice metadata: Bill #, Date & Time, Cashier/Operator Name.
  - Customer info: NIT, Name, Delivery Address, Phone.
  - Line items: Product Description, Batch #, Expiration Date, Quantity, Unit Price, Line Total.
  - Summary: Subtotal, Discounts, Grand Total (rounded integer peso), Payment Terms.

---

### 5. Verification & Testing Strategy

- **Feature Tests (`tests/Feature/Pages/Sales/SaleAndInvoicingTest.php`)**:
  1. Access control: Guest redirected, warehouse assistant/admin authorized.
  2. Customer reactive search by NIT and business name.
  3. Barcode product lookup and error handling for missing medicines.
  4. FEFO algorithm assigns closest expiring batches first.
  5. Multi-lot split when requested quantity exceeds single batch stock.
  6. Manual lot override and row lock persistence.
  7. Unit price discount modification.
  8. Credit sale validations (mandatory due date, credit limit ceiling enforcement).
  9. Integer rounding of grand total to nearest peso.
  10. Concurrency stock validation (`lockForUpdate`).
  11. Successful sale creation: stock decrement, inventory movement logs, timestamps and user audit.
  12. Prohibition of invoice deletion and successful invoice annulment with stock restoration.
  13. Completeness check auto-resolution of remaining units.
  14. PDF generation and response assertions.
