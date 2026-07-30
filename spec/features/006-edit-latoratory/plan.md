# 006 · Edit Laboratory — Plan

## Approach

We will implement the Laboratory Edit feature using a Volt component, an update request, and updates to the service layer.

- **Service Layer (`App\Services\LaboratoryService`):**
  - Implement `update(Laboratory $laboratory, array $data): Laboratory` to persist the modifications and assign the current authenticated user's ID to `updated_by`.
- **Form Request (`App\Http\Requests\Laboratory\UpdateLaboratoryRequest`):**
  - Create the request class validating the rules (name is required, max 255 characters, and unique in active laboratories except current, description max 255 characters).
- **Livewire Volt Components:**
  - **Edit View (`resources/views/livewire/laboratories/edit.blade.php`):**
    - Standard form with fields for `name` and `description`.
    - Automatically mounts the laboratory's existing values.
    - Handles submission, calls the validation request, calls `LaboratoryService` to persist, sets flash notification, and redirects back to the index.
  - **Index View (`resources/views/livewire/laboratories/index.blade.php`):**
    - Add an "Edit" button to each active laboratory row pointing to the edit route.

## Implementation Steps

1. **Service Layer Setup:**
   - In [LaboratoryService.php](file:///home/one-centavo/Proyectos/tifah/app/Services/LaboratoryService.php), implement the `update` method:
     ```php
     public function update(Laboratory $laboratory, array $data): Laboratory
     {
         $data['updated_by'] = auth()->id();
         $laboratory->update($data);
         return $laboratory;
     }
     ```

2. **Form Request validation:**
   - Create `app/Http/Requests/Laboratory/UpdateLaboratoryRequest.php` extending `LaboratoryRequestBase`.
   - Implement the `rules(?int $ignoreId = null): array` method to validate the inputs:
     - `name`: `['required', 'string', 'max:255', Rule::unique('laboratories', 'name')->ignore($ignoreId)->whereNull('deleted_at')]`.
   - Provide Spanish error messages mirroring `StoreLaboratoryRequest`:
     - Name required: `"El nombre del laboratorio es obligatorio."`
     - Name too long: `"El nombre del laboratorio no debe exceder los 255 caracteres."`
     - Name already exists: `"Este laboratorio ya se encuentra registrado."`
     - Description too long: `"La descripción no debe exceder los 255 caracteres."`

3. **Livewire Component - Edit Page:**
   - Create Volt component at `resources/views/livewire/laboratories/edit.blade.php` using layout `layouts.app`.
   - Define property `$laboratory` and bound properties `$name` and `$description`.
   - In `mount(Laboratory $laboratory)`:
     - Set properties `$this->laboratory = $laboratory;`, `$this->name = $laboratory->name;`, `$this->description = $laboratory->description;`.
   - In `save(LaboratoryService $laboratoryService)` action:
     - Run validation using `UpdateLaboratoryRequest` rules, passing `$this->laboratory->id`.
     - Call `$laboratoryService->update($this->laboratory, $validated)`.
     - Flash success message: `"El laboratorio ha sido actualizado con éxito."`
     - Redirect to `laboratories.index` using `wire:navigate`.

4. **Add Edit button on Index Page:**
   - Modify `resources/views/livewire/laboratories/index.blade.php`.
   - Locate the active laboratories action cell (near line 303).
   - Add an edit button/link that points to the edit route:
     ```html
     <a href="{{ route('laboratories.edit', $laboratory->id) }}" wire:navigate
        class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors gap-1 cursor-pointer">
         <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
         </svg>
         <span>Editar</span>
     </a>
     ```

5. **Routing:**
   - Add the edit route to `routes/web.php` inside the auth group:
     `Volt::route('laboratories/{laboratory}/edit', 'laboratories.edit')->name('laboratories.edit');`

6. **Automated Testing:**
   - Create a feature test file `tests/Feature/Pages/Laboratories/EditLaboratoryTest.php`.
   - Cover:
     - Guests are redirected to the login page when trying to access the edit route.
     - Authorized users can render the page.
     - Form loads existing laboratory values correctly.
     - Name field validation works (required, max length 255).
     - Name unique validation works (fails if exists on active laboratories, ignores soft-deleted ones, ignores current laboratory).
     - Successful update registers `updated_by` and `updated_at`, keeps creation audits, shows success flash message, and redirects to laboratories index.

## Risks & Mitigations

- **Uniqueness Check Collision with Soft-Deleted records:**
  - If unique check does not filter by `deleted_at IS NULL`, renaming a laboratory to a soft-deleted one will incorrectly trigger validation errors.
  - _Mitigation:_ We explicitly use `whereNull('deleted_at')` in the uniqueness validation rule.
