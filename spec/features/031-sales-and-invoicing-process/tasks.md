# 031 · Sales and Invoicing Process — Tasks

## 1. Database & Migrations
- [x] Ensure `bills` table includes `invoice_number`, `payment_method`, `payment_due_date`, `total_amount`, `annulled_reason`, `annulled_by`, and `annulled_at`.
- [x] Ensure `bill_details` table includes `bill_id`, `lot_id`, `quantity`, `unit_price`, and `subtotal`.
- [x] Add relationship methods in `Bill`, `BillDetail`, `Lot`, and `Customer` models.
- [x] Update `Bill` model to prevent deletion via model events / soft delete / restrictions.


## 2. Business Logic & Service Layer
- [x] Create `App\Services\BillService` / `SaleService`.
- [x] Implement `allocateFefoLots()` algorithm to auto-suggest and distribute quantities across earliest expiring lots.
- [x] Implement support for locked rows in FEFO distribution.
- [x] Implement `validateCreditEligibility()` checking customer debt limit against proposed invoice total.
- [x] Implement `createSale()` with `DB::transaction()` and pessimistic locking (`lockForUpdate`).
- [x] Implement stock decrement and `InventoryMovement` logging (`type = 'sale'`).
- [x] Implement `annulBill()` with stock restitution to original lots and compensating `InventoryMovement` (`type = 'annulment_return'`).
- [x] Implement mathematical integer rounding to the nearest peso for invoice totals.


## 3. Livewire Volt Components & UI
- [x] Create `resources/views/livewire/sales/create.blade.php` Volt component for point-of-sale invoicing.
- [x] Implement reactive customer search by NIT / commercial name.
- [x] Implement barcode scanning input with auto-focus and manual name search autocomplete.
- [x] Implement missing medicine notification and quick registration modal shortcut.
- [x] Render FEFO lot selector with expiry dates, stock countdowns, and warning badges.
- [x] Add manual lot override selection with row-locking toggle.
- [x] Render interactive temporary cart table with editable unit price for discounts and item deletion.
- [x] Implement payment method selector (Cash, Transfer, Credit) with conditional credit due date input.
- [x] Implement pre-save quantity completeness validator with "Autocompletar Lotes Faltantes" action.
- [x] Implement final submission trigger with concurrency error handling and success feedback.
- [x] Create `resources/views/livewire/bills/index.blade.php` to list invoices with status badges, search, and filtering.
- [x] Create invoice details modal and "Anular Factura" workflow with mandatory reason prompt.

## 4. PDF Generation & Printing
- [ ] Create PDF template Blade view (`resources/views/pdf/invoice.blade.php`) formatted with company header, customer details, itemized batch breakdown, payment terms, and totals.
- [ ] Implement `BillPdfController` to stream/download the generated PDF.
- [ ] Add "Imprimir / Descargar Factura" button upon sale confirmation and in invoice list/detail views.

## 5. Routing & Navigation
- [x] Register `/sales/create` and `/bills` routes under `auth` middleware in `routes/web.php`.
- [ ] Add "Ventas / Facturación" links in main navigation menus.


## 6. Testing & Quality Assurance
- [ ] Write feature tests for customer search and selection.
- [ ] Write feature tests for barcode scan lookup and non-existent medicine handling.
- [ ] Write unit & feature tests for FEFO multi-lot auto-allocation and row lock overrides.
- [ ] Write feature tests for price editing / discount overrides.
- [ ] Write feature tests for credit limit validations and required due dates.
- [ ] Write feature tests for concurrency stock locking and race condition prevention.
- [ ] Write feature tests for integer rounding of totals.
- [ ] Write feature tests for stock decrement, audit logging, and immutability (no delete).
- [ ] Write feature tests for invoice annulment and stock restitution.
- [ ] Write feature tests for PDF invoice generation.
- [ ] Run `php artisan test` to ensure all tests pass without regressions.
- [ ] Update `spec/constitution/roadmap.md` to reflect the new feature in the roadmap.
