# 002 · Edit Category & Category Management — Tasks

- [ ] Add the `update(Category $category, array $data)` and `delete(Category $category)` methods to `CategoryService` at [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php).
- [ ] Create the `UpdateCategoryRequest` class at `app/Http/Requests/Category/UpdateCategoryRequest.php` extending `CategoryRequestBase`.
- [ ] Create the Category Management Index component at `resources/views/livewire/categories/index.blade.php`.
- [ ] Implement the listing table, pagination, and the delete action in the Index component.
- [ ] Create the Category Edit component at `resources/views/livewire/categories/edit.blade.php`.
- [ ] Bind properties, implement form validation using `UpdateCategoryRequest`, and implement the save action.
- [ ] Add the listing of belonging medicines at the bottom of the Edit page.
- [ ] Register routes `categories` (Index) and `categories/{category}/edit` (Edit) in `routes/web.php`.
- [ ] Update the navigation sidebar link to point to `categories.index` instead of `categories.create` in layouts.
- [ ] Write Pest tests for the Category Management Index page under `tests/Feature/Pages/Categories/CategoryManagementTest.php` (covering access, list, soft delete, and links).
- [ ] Write Pest tests for the Edit Category page under `tests/Feature/Pages/Categories/EditCategoryTest.php` (covering access, validations, unique constraint checks, update, and medicine listing).
- [ ] Run the test suite using `docker compose exec app php artisan test` and verify that all tests pass.
- [ ] Format and lint the codebase using Pint and PHPStan to ensure compliance.
- [ ] Validate implementation against all acceptance criteria defined in [spec.md](file:///home/one-centavo/Proyectos/tifah/spec/features/002-edit-category/spec.md).
- [ ] Move the feature to the completed section in [roadmap.md](file:///home/one-centavo/Proyectos/tifah/spec/constitution/roadmap.md).
