# 027 · Register Customer — Tasks

## Database, Model, Factory & Requests
- [ ] Configure relations in `Customer` model for `creator`, `updater`, `deleter`, and `bills`.
- [ ] Create `database/factories/CustomerFactory.php` to define the fake data generator for the customer model, including DV calculation.
- [ ] Create `app/Http/Requests/Customer/StoreCustomerRequest.php` extending `CustomerRequestBase` with unique validation rule on active records.
- [ ] Create `app/Http/Requests/Customer/UpdateCustomerRequest.php` extending `CustomerRequestBase` with unique validation rule excluding the current record.

## Service layer logic
- [ ] Create `app/Services/CustomerService.php`.
- [ ] Implement `create(array $data)` in `CustomerService` setting `created_by`.
- [ ] Implement `update(Customer $customer, array $data)` in `CustomerService` setting `updated_by`.
- [ ] Implement `delete(Customer $customer)` in `CustomerService` with billing referential integrity check and `deleted_by` logging on soft delete.

## Routing, Layout & Livewire Components
- [ ] Add routes for customers (index, create, edit) in `routes/web.php` inside the auth group.
- [ ] Add "Clientes" link to navigation layout in `resources/views/layouts/navigation.blade.php`.
- [ ] Create `resources/views/livewire/customers/index.blade.php` with search, filter, and confirmation delete modal.
- [ ] Create `resources/views/livewire/customers/create.blade.php` with reactive DV calculation and forms.
- [ ] Create `resources/views/livewire/customers/edit.blade.php` to allow updating customer information.

## Verification & Tests
- [ ] Create Pest feature test file `tests/Feature/Pages/Customers/CustomerManagementTest.php`.
- [ ] Implement guest redirect tests.
- [ ] Implement active customer listing, search, and status filtering tests.
- [ ] Implement reactive DV digit calculation test.
- [ ] Implement validations tests (required fields, lengths, emails, unique NIT rules).
- [ ] Implement create and update tests including audit columns assertion.
- [ ] Implement soft delete and deleter ID check test.
- [ ] Implement referential integrity check blocking deletion if bills exist.
- [ ] Validate tests pass by running tests command.
- [ ] Register this feature in `spec/constitution/roadmap.md`.
