# 001 · Create Category — Plan

## Approach

We will implement the Category Registration feature using a decoupled architecture that keeps presentation and business logic separate:

- A dedicated Service layer (`App\Services\CategoryService`) will encapsulate the creation business logic, such as performing the Eloquent model creation and automatically injecting the authenticated user's ID into the `created_by` field.
- A Livewire Volt Single File Component (`resources/views/livewire/pages/categories/create.blade.php`) will manage the user interface, bind form state, execute validation rules, call the service layer, dispatch UI notifications, and reset the form.

This architecture ensures high testability, clean controller/view files, and isolates the database transaction logic from the HTTP/UI layer.

## Implementation

1. **Service Layer Setup:**
    - Create a new service class at [CategoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/CategoryService.php) under `App\Services`.
    - Implement a public method `create(array $data): Category` that accepts validated inputs.
    - Automatically assign `created_by` to the authenticated user's ID via `auth()->id()`.
    - Create and return the `Category` model instance.

2. **Validation Strategy:**
   - Create a new concrete FormRequest class `StoreCategoryRequest` at `App\Http\Requests\Category\StoreCategoryRequest` extending `CategoryRequestBase`.
   - Implement the `rules()` method by merging the specific name validation with the common rules from the base class:
     - `name`: `['required', 'string', 'min:3', 'max:50', 'unique:categories,name']`
     - Common rules (`description`, `is_cold_chain`, `is_special_control`) retrieved by calling `$this->commonRules()`.
   - In the Livewire component, dynamically use the rules defined in `StoreCategoryRequest` for validation.

3. **Livewire Component Development:**
    - Create a new Volt component at [create.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/livewire/pages/categories/create.blade.php).
    - Bind form properties (`$name`, `$description`, `$is_cold_chain`, `$is_special_control`) using `wire:model`.
    - In the `save` action, call `$this->validate()` to perform validation.
    - Inject or instantiate `CategoryService` to persist the validated data.
    - After successful creation, dispatch a success notification event and call the reset method to clear the form.

4. **Routing and Layout Integration:**
    - Add a new route `/categories/create` to `routes/web.php` protected by the `auth` middleware.
    - Add a navigation link/tab to the layout navigation sidebar at [app.blade.php](file:///home/one-centavo/Proyectos/tifah/resources/views/layouts/app.blade.php) for ease of access.

5. **Testing & Quality Assurance:**
    - Create a feature test file at `tests/Feature/Pages/Categories/CreateCategoryTest.php`.
    - Write tests using Pest to assert page accessibility for authorized users, validation failures, successful creation, and that the service layer correctly links the current user.

## Decisions

- **Service Layer Pattern** — We explicitly encapsulate the model creation logic in `CategoryService`. This avoids bloating the Livewire component and enables reuse in seeders or API endpoints.
- **FormRequest Reusability** — We define a concrete `StoreCategoryRequest` extending `CategoryRequestBase` to centralize validation rules. This keeps the validation rules reusable across other parts of the application (e.g. APIs or future controllers) and keeps the Volt component focused on handling user interactions and UI updates.

## Risks

- **Concurrent Duplicate Names** — The unique constraint on the database could cause a 500 error if two users try to register the exact same category name simultaneously.
    - _Mitigation:_ Ensure `unique:categories,name` validation is executed on submit and database transactions are caught if there is an integrity constraint exception.
- **Authorization Bypass** — Users without appropriate privileges might try to reach or submit the creation endpoint.
    - _Mitigation:_ Restrict route access to authenticated users via the `auth` middleware. If more granular role-based access control (RBAC) is introduced later, check permissions inside the service or Livewire component.
