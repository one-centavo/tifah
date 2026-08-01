# 014 · Edit Medicine & Medicine Catalog Management

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to edit existing medicines in the catalog. To protect the integrity of master data once a product has inventory history, the system restricts the editing of core "Master Data" fields (Names, Technical Specifications, and Legal Entities) once the medicine has associated lots or sales. If the medicine has no lots or sales, all fields remain editable.

Additionally, it provides a Barcode Manager within the edit form to display all barcodes linked to the medicine, allow scanning or adding new barcodes without deleting existing ones, and allow correcting typos on existing barcodes only if they were added in the current session or the medicine has no inventory movements.

## Why

To keep the operational data of medicines (prices, minimum stock, category, description) up to date while protecting historical inventory, purchase, and sales records from corruption. Changing master data like generic names or laboratories after inventory transactions occur would break traceability.

## Acceptance criteria

### 1. Access and Loading
- [ ] Authorized users (Administrators or Warehouse Assistants) can access the edit form at `/medicines/{medicine}/edit`.
- [ ] Guest or unauthorized users attempting to access the page are redirected to the login page.
- [ ] The edit form loads all current data of the selected medicine.

### 2. Field Mutability Constraints (Read-Only Logic)
- [ ] **Always Editable Fields:** The user can freely modify the following fields at any time:
  - **Sale Price** (`selling_price`): Required, numeric positive.
  - **Minimum Stock** (`min_stock`): Required, integer greater than or equal to zero.
  - **Description** (`description`): Optional, max 500 characters.
  - **Category** (`category_id`): Required. (Updates the Cold Chain and Special Control flags accordingly, but allows manual override).
- [ ] **Master Data Fields:**
  - **Commercial Name** (`name`) and **Generic Name** (`generic_name`).
  - **Technical Specifications:** Concentration Value (`concentration_value`), Concentration Unit (`concentration_unit_id`), Container (`container_id`), Content Quantity (`content_quantity`), Content Unit (`content_unit_id`).
  - **Legal Entities:** Laboratory (`laboratory_id`) and Sanitary Registry INVIMA (`sanitary_registry_id`).
- [ ] **Read-Only Condition:**
  - The system must verify if the medicine has any registered lots (`lots` table) or sales (`bill_details` table via lots).
  - If the medicine **has** lots or sales, the Master Data fields must be rendered in read-only mode (disabled or label-only) and cannot be modified.
  - If the medicine **does not have** lots or sales, all Master Data fields must be fully editable.

### 3. Master Data Duplicity Check
- [ ] If Master Data fields are edited, the system must validate before saving that the new combination of `[Commercial Name + Generic Name + Concentration (Value & Unit) + Presentation (Container, Quantity, Unit) + Laboratory]` does not already exist in the catalog for another active medicine (excluding the medicine currently being edited).
- [ ] If a duplicate is found, the system must block the update and show a warning message or modal indicating that a medicine with that combination already exists.

### 4. Barcode List Manager
- [ ] The edit form must feature a list manager showing all barcodes linked to the medicine.
- [ ] **Add New Barcodes:**
  - Users can add or scan a new barcode to the medicine's list.
  - The new barcode must not delete previous ones.
  - The system must validate that the new barcode is unique across the entire database of barcodes (`medicine_barcodes.barcode`).
- [ ] **Edit/Delete Existing Barcodes:**
  - An existing barcode in the list can ONLY be edited or removed if:
    - It was added during the *current editing session* (i.e. not yet saved to the database or created during this session), OR
    - The barcode's medicine has no inventory movements (lots/sales) associated.
  - Otherwise, existing barcodes must be read-only and cannot be deleted or modified.

### 5. Audit Logging
- [ ] The system must automatically set the `updated_by` field to the authenticated user's ID and update `updated_at`.
- [ ] The original creation data (`created_by`, `created_at`) must remain unmodified.

### 6. Validation and Feedback
- [ ] Validation errors must display adjacent to their corresponding fields.
- [ ] The system prevents saving if mandatory fields are missing or formats are invalid.
- [ ] On successful update, the system must show a success confirmation message in Spanish (Colombia) and redirect or refresh the view showing the updated information.

## Out of scope

- Bulk editing multiple medicines at once.
- Deleting barcodes that already have inventory history.
