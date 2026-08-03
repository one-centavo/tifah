# 001 · Create Category

**Status:** Completed

## What it does

Allows authenticated users (specifically, pharmacy managers or authorized distributors) to register a new product category in the system through a web interface. The user provides a unique name, an optional description, and sets logistical flags indicating whether the category requires cold chain storage or is a special control substance.

## Why

To classify products within the distributor system and establish default logistics constraints (such as cold chain or special control) at the category level. This classification ensures regulatory compliance, guides warehouse handling procedures, and helps prevent human errors in storage and distribution.

## Acceptance criteria

- [ ] Authorized users can access the category creation form page at `/categories/create`.
- [ ] Unauthorized guest users attempting to access the creation form are redirected to the login page.
- [ ] The `name` field is required, must be unique in the database, and must have a length between 3 and 50 characters.
- [ ] The `description` field is optional and has a maximum length of 255 characters.
- [ ] The `is_cold_chain` and `is_special_control` fields are optional boolean fields, defaulting to unchecked/false.
- [ ] Submitting the form with invalid data displays corresponding validation error messages in Spanish (Colombia) next to the invalid fields without losing other inputs.
- [ ] Submitting the form with valid data invokes the business logic to create the category, automatically assigns the authenticated user's ID to `created_by`, and saves the record in the database.
- [ ] Upon successful creation, the system displays a clear success notification message in Spanish (Colombia) and resets the form fields to their defaults.

## Out of scope

- Updating, viewing, or deleting existing categories (to be addressed in subsequent features).
- Bulk uploading categories from spreadsheets or CSV files.
- Searching, filtering, or listing categories on this page.
