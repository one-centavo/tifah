# 002 · Edit Category — Tasks

- [ ] Add the `update(Category $category, array $data)` method to `CategoryService` at [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php).
- [ ] Create the `UpdateCategoryRequest` class at `app/Http/Requests/Category/UpdateCategoryRequest.php` extending `CategoryRequestBase`, with the rules validating name uniqueness except itself and filtering soft deletes.
- [ ] Create the Livewire Volt page component at `resources/views/livewire/categories/edit.blade.php`.
- [ ] Set up component properties, bind category model in `mount`, and implement the validation rules call pointing to `UpdateCategoryRequest`.
- [ ] Design the UI form in the Volt component to load existing values for name, description, is_cold_chain, and is_special_control.
- [ ] Implement the `save` method in the Volt component to trigger validation, call `CategoryService::update`, and flash a success message.
- [ ] Add a list/table section in the view showing the medicines belonging to the category, using `$category->medicines`.
- [ ] Add the route `categories/{category}/edit` in `routes/web.php` named `categories.edit` inside the `auth` middleware group.
- [ ] Create the feature test suite at `tests/Feature/Pages/Categories/EditCategoryTest.php` using Pest to cover authorization, validation, unique checks, medicine display, and database updates.
- [ ] Run the test suite using `docker compose exec app php artisan test` and verify that all tests pass.
- [ ] Format and lint the codebase using Pint and PHPStan to ensure compliance.
- [ ] Validate implementation against all acceptance criteria defined in [spec.md](file:///home/one-centavo/Proyectos/tifah/spec/features/002-edit-category/spec.md).
- [ ] Move the feature to the completed section in [roadmap.md](file:///home/one-centavo/Proyectos/tifah/spec/constitution/roadmap.md).
