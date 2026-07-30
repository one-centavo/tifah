# 002 · Edit Category & Category Management

**Status:** Completed

## What it does

Provides a central Category Management dashboard (Index page) at `/categories` where authorized users (specifically, administrators or warehouse assistants) can view all active product categories. This dashboard serves as the CRUD hub, offering options to create a new category, navigate to the edit form of a category, and delete a category.

Additionally, allows users to modify the details of an existing product category via the Edit page at `/categories/{category}/edit`. The Edit page displays the current values of the category (Name, Description, Cold Chain, and Special Control) and lists the medicines currently belonging to this category, illustrating which products are affected by the changes.

## Why

To maintain an organized and up-to-date classification of products. Having a central management interface (Index) combined with precise editing capabilities allows warehouse and administrative staff to easily correct sanitization or logistical configuration errors, ensuring regulatory compliance and correct product handling.

## Acceptance criteria

### Category Management (Index Page)
- [x] Authorized users (administrators or warehouse assistants) can access the Category Management index page at `/categories`.
- [x] Unauthorized guest users attempting to access the page are redirected to the login page.
- [x] The index page displays a clear, paginated list or table of all registered categories, showing their Name, Description, Cold Chain Management, and Special Control status.
- [x] The index page includes a prominent button to navigate to the category creation page (`/categories/create`).
- [x] Each category row in the table has a button or link to navigate to its respective Edit page (`/categories/{category}/edit`).
- [x] Each category row has an option to delete the category (soft delete), automatically logging `deleted_by` with the authenticated user's ID.

### Category Edit Page
- [x] Authorized users can access the edit page at `/categories/{category}/edit` by clicking the edit option in the category list.
- [x] The edit form loads the target category data: Name, Description, Cold Chain Management, and Special Control.
- [x] The `name` field is required and prevents saving if left empty.
- [x] The `name` field has a length constraint of minimum 3 characters and maximum 50 characters.
- [x] The `description` field is optional and has a maximum length of 255 characters.
- [x] The system validates that the modified `name` is unique in the database among active categories, excluding the category currently being edited.
- [x] Logistical switches for "Cold Chain Management" (`is_cold_chain`) and "Special Control" (`is_special_control`) are loaded with their current database value and can be modified.
- [x] The system automatically records the authenticated user's ID as the modifier (`updated_by`) and updates the `updated_at` timestamp while keeping the original creation information intact.
- [x] Upon submitting invalid data, the system shows clear validation error messages in Spanish (Colombia) next to the corresponding fields without saving.
- [x] The edit page displays a list of medicines belonging to the category, illustrating which products are affected.
- [x] Upon a successful update, the system displays a success confirmation message in Spanish (Colombia) and the category remains active and enabled for use.

## Out of scope

- Bulk importing or exporting categories from spreadsheets (handled in another feature).
- Reassigning medicine categories directly from this management view.
