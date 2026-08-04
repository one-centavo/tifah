# 027 · Register Customer — Tasks

## Database, Model, Factory & Requests
- [x] Configure relations in `Customer` model for `creator`, `updater`, `deleter`, and `bills`.
- [x] Create `database/factories/CustomerFactory.php` to define the fake data generator for the customer model, including DV calculation.
- [x] Create `app/Http/Requests/Customer/StoreCustomerRequest.php` extending `CustomerRequestBase` with unique validation rule on active records.
- [x] Create `app/Http/Requests/Customer/UpdateCustomerRequest.php` extending `CustomerRequestBase` with unique validation rule excluding the current record.

## Service layer logic
- [x] Create `app/Services/CustomerService.php`.
- [x] Implement `create(array $data)` in `CustomerService` setting `created_by`.
- [x] Implement `update(Customer $customer, array $data)` in `CustomerService` setting `updated_by`.
- [x] Implement `delete(Customer $customer)` in `CustomerService` with billing referential integrity check and `deleted_by` logging on soft delete.

## Routing, Layout & Livewire Components
- [x] Add routes for customers (index, create, edit) in `routes/web.php` inside the auth group.
- [x] Add "Clientes" link to navigation layout in `resources/views/layouts/navigation.blade.php`.
- [x] Create `resources/views/livewire/customers/index.blade.php` with search, filter, and confirmation delete modal.
- [x] Create `resources/views/livewire/customers/create.blade.php` with reactive DV calculation and forms.
- [x] Create `resources/views/livewire/customers/edit.blade.php` to allow updating customer information.

## Verification & Tests
- [x] Create Pest feature test file `tests/Feature/Pages/Customers/CustomerManagementTest.php`.
- [x] Implement guest redirect tests.
- [x] Implement active customer listing, search, and status filtering tests.
- [x] Implement reactive DV digit calculation test.
- [x] Implement validations tests (required fields, lengths, emails, unique NIT rules).
- [x] Implement create and update tests including audit columns assertion.
- [x] Implement soft delete and deleter ID check test.
- [x] Implement referential integrity check blocking deletion if bills exist.
- [x] Validate tests pass by running tests command.
- [x] Register this feature in `spec/constitution/roadmap.md`.
