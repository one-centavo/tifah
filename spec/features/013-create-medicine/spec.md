# 013 · Create Medicine & Medicine Catalog Management

**Status:** Draft

## What it does

Allows authorized users (Administrators or Warehouse Assistants) to register new medicines in the system to standardize the product catalog and enable subsequent batch and stock management. The registration process captures detailed technical information, validates barcodes (manually entered or laser-scanned), automatically generates internal barcodes if factory ones are missing, maps relationships with categories, laboratories, and sanitary registries, and prevents duplicates. It also supports soft deletion for catalog maintenance, auto-generates the presentation description, and provides quick registration modals for missing laboratories and sanitary registries.

## Why

To maintain a standardized, clean, and compliant product catalog that serves as the foundation for inventory control, batch tracing, and sales, while minimizing data-entry friction and preventing duplicate items.

## Acceptance criteria

### 1. Registration Form Fields
The system must display a registration form with the following fields:
- **Barcode** (`barcode`): Required. Can be scanned via laser or entered manually.
- **Commercial Name** (`name`): Required, maximum 100 characters.
- **Generic Name** (`generic_name`): Optional, maximum 255 characters.
- **Category** (`category_id`): Required. Dropdown selector from categories.
- **Concentration Value** (`concentration_value`): Required, positive numeric value (allows decimals).
- **Concentration Unit** (`concentration_unit_id`): Required. Selector from predefined units (e.g., mg, ml, g).
- **Container** (`container_id`): Required. Selector from predefined options (e.g., Caja, Frasco, Ampolla).
- **Content Quantity** (`content_quantity`): Required, integer/numeric value greater than zero.
- **Content Unit** (`content_unit_id`): Required. Selector from predefined options (e.g., Tabletas, ml, Cápsulas).
- **Laboratory** (`laboratory_id`): Required. Selector from registered laboratories.
- **Sanitary Registry (INVIMA)** (`sanitary_registry_id`): Required. Search and selector from registered sanitary registries.
- **Cold Chain** (`is_cold_chain`): Boolean checkbox.
- **Special Control** (`is_special_control`): Boolean checkbox.
- **Minimum Stock** (`min_stock`): Required, integer value greater than or equal to zero.
- **Sale Price** (`selling_price`): Required, numeric value supporting decimals.
- **Description** (`description`): Optional, maximum 500 characters.

### 2. Barcode Handling and Validation
- **Laser Scanner / Keyboard Input**: The barcode input field must support both inputs.
- **Format Constraint**: If entered manually, the barcode must consist strictly of digits (numbers only) and have a length of between 8 and 14 characters.
- **Auto-Generation**: If the medicine has no factory barcode, the system must allow the user to auto-generate a unique internal barcode (e.g., using a dedicated button).
- **Uniqueness**: The barcode must be unique in the system. The barcode is saved in the `medicine_barcodes` table linked via the medicine ID, ensuring no duplicate existences.

### 3. Category Automation and Manual Override
- When the user selects a Category, the system must automatically check/uncheck the **Cold Chain** (`is_cold_chain`) and **Special Control** (`is_special_control`) options based on how that selected Category is configured.
- The user must be allowed to manually override (toggle) these options before saving in case the specific medicine is an exception to its category rules.

### 4. Automatic Presentation String Generation
- The system must automatically combine the Container, Content Quantity, and Content Unit to generate the presentation description shown to customers (e.g., Container "Caja", Quantity "30", Content Unit "Tabletas" format: `"Caja x 30 Tabletas"`).
- This is represented dynamically in the UI and via a model accessor/attribute.

### 5. Quick Registration Modals
- **Quick Laboratory Registration**: Next to the laboratory dropdown, a button/link must allow registering a new laboratory on the fly. It opens a modal with:
  - **Name** (required, unique, maximum 255 characters).
  - **Description** (optional, maximum 255 characters).
  - On submit, saves the laboratory, closes the modal, and auto-selects the new laboratory.
- **Quick Sanitary Registry Registration**: Next to the sanitary registry search dropdown, a button/link must allow registering a new sanitary registry on the fly. It opens a modal with:
  - **Sanitary Registry Number** (required, unique, uppercase normalized, official regex format matching).
  - **Manufacturer Laboratory** (required, selector/search).
  - **Expiration Date** (required, must be in the future).
  - **Status** (default "Vigente").
  - On submit, saves the registry, closes the modal, and auto-selects the new registry.

### 6. Duplicity Check (Combination Validation)
- Before saving, the system must validate that the combination of `[Commercial Name + Generic Name + Concentration (Value & Unit) + Presentation (Container, Quantity, Unit) + Laboratory]` is unique in the catalog.
- If a complete match is found:
  - Prevent the registration.
  - Display a warning modal informing that the product already exists.
  - The modal must offer two options:
    1. **Link Barcode**: Bind the new barcode to the existing medicine (inserting a new record in `medicine_barcodes` for that medicine) and complete the operation.
    2. **Cancel**: Close the modal, keep the form open, and allow the user to correct the data.

### 7. Form Actions and UI Feedback
- Validation errors must display adjacent to their corresponding fields.
- On successful save, show a success confirmation message in Spanish and clear/reset the form immediately for the next entry.

### 8. Soft Deletion
- The catalog management view must include an "Eliminar" (Delete/Archive) action.
- Deleting a medicine does not remove it from the database (logical soft delete via `deleted_at`).
- Automatically record the authenticated user ID in the `deleted_by` column and the timestamp in `deleted_at`.
- Soft-deleted medicines are hidden from active catalog views but preserved for historical audits, batches, and sales records.

## Out of scope
- Editing existing medicines (this will be handled in a separate feature task).
- Bulk importing medicines from Excel/CSV files.
