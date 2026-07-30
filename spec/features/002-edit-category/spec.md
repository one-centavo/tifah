# 002 · Edit Category

**Status:** In Progress

## What it does

Allows authenticated users (specifically, administrators or warehouse assistants) to modify the information of an existing product category. Users can update the category name, description, and logistical flags (cold chain storage requirement or special control substance). The page also displays a list of medicines currently belonging to this category, illustrating which products will be affected by any changes.

## Why

To keep category classifications up to date and correct configuration errors in sanitary or logistical settings. Modifying these constraints ensures that regulatory and warehouse procedures remain accurate, which directly prevents compliance issues or handling errors in the warehouse.

## Acceptance criteria

- [ ] Authorized users (administrators or warehouse assistants) can access the category edit page at `/categories/{category}/edit`.
- [ ] Unauthorized guest users attempting to access the edit page are redirected to the login page.
- [ ] The edit page successfully loads the target category data into the form, including the fields: Name, Description, Cold Chain Management, and Special Control.
- [ ] The `name` field is required and must prevent saving if left empty.
- [ ] The `name` field must have a minimum length of 3 characters and a maximum length of 50 characters.
- [ ] The `description` field is optional and has a maximum length of 255 characters.
- [ ] The system validates that the modified `name` is unique in the database among active categories, excluding the category currently being edited.
- [ ] The switches/checkboxes for "Cold Chain Management" (`is_cold_chain`) and "Special Control" (`is_special_control`) are loaded with their previously saved values, allowing the user to modify them.
- [ ] The system automatically records the authenticated user's ID as the modifier (`updated_by`) and updates the modification timestamp (`updated_at`), while preserving the original creation details (`created_by`, `created_at`).
- [ ] In case of invalid inputs (e.g., duplicated name or incorrect format), the system shows a clear validation error message in Spanish (Colombia) next to the corresponding field before saving.
- [ ] The edit page displays a list of medicines currently belonging to the category, highlighting which products are affected by the changes.
- [ ] Upon a successful update, the system displays a clear confirmation message in Spanish (Colombia) and the category remains active and enabled for use.

## Out of scope

- Creating, listing, or deleting categories on this specific edit page (handled by separate features).
- Changing the category of a medicine from this view (medicines are updated in the medicine management module).
