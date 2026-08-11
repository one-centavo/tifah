# 029 · Show and Filter Customers — Tasks

## Database, Models & Service Layer
- [x] Ensure soft delete scopes and relationships are configured in `App\Models\Customer`.
- [x] Implement/verify logical deletion logic in `App\Services\CustomerService::delete()`.
- [x] Validate referential integrity checks (e.g., historical bills prevention) before archiving.

## Livewire Volt Component & Filter Logic
- [x] Create/update `resources/views/livewire/customers/index.blade.php` Volt component.
- [x] Bind `$search` property with `wire:model.live.debounce.300ms` for partial matching on Razón Social and NIT.
- [x] Bind `$city` property with `wire:model.live` select dropdown for filtering by City.
- [x] Bind `$status` property with `wire:model.live` select dropdown for filtering by Active, Archived, or All records.
- [x] Set default status to `'active'` so only active customers load initially.
- [x] Implement column sorting mechanism for Razón Social (`sortBy('name')`).
- [x] Add pagination reset hooks (`updatingSearch`, `updatingCity`, `updatingStatus`).

## User Interface & Design System
- [x] Render responsive customer list table with Razón Social, NIT (with DV), Ciudad, Teléfono, and Estado badge.
- [x] Display empty state message: `"No se encontraron clientes que coincidan con los filtros aplicados"` when query yields no results.
- [x] Add action button for 'Editar' linking to `/customers/{customer}/edit` (HU 21).
- [x] Add action button for 'Archivar' triggering soft deletion confirmation modal.
- [x] Implement deletion confirmation modal with clear confirmation and error messaging.

## Testing & Verification
- [x] Write Pest test for unauthenticated access redirection.
- [x] Write Pest test verifying authorized roles can access `/customers`.
- [x] Write Pest test verifying default load displays only active customers.
- [x] Write Pest test for global search filtering by Razón Social and NIT.
- [x] Write Pest test for city selection dropdown filter.
- [x] Write Pest test for status filter (active vs archived).
- [x] Write Pest test verifying empty state message when no records match.
- [x] Write Pest test verifying sorting by Razón Social.
- [x] Write Pest test verifying Edit link navigation and Soft Delete archiving action.
- [x] Update `spec/constitution/roadmap.md` with feature `029 · Show and Filter Customers (HU 22)`.

