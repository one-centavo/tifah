# 032 · Expiration Alerts Management (HU 25)

**Status:** Completed

## What it does

Provides **Warehouse Assistants (Auxiliar de Bodega)** and **Administrators (Administrador)** with an automated early warning system for expiring pharmaceutical batches.

The feature:
1. Performs daily automated scans of active inventory batches with remaining shelf life within 30, 60, and 90 days.
2. Displays a persistent visual notification badge in the application header indicating the total number of batches in expiration warning status.
3. Classifies batches using a standardized urgency color code: **Red** (30 days or fewer), **Orange** (31 to 60 days), and **Yellow** (61 to 90 days).
4. Provides a dedicated, filterable Expiration Alerts screen displaying medicine names, batch numbers, remaining stock, exact expiration dates, days remaining, and monetary valuations.
5. Sends an automated weekly consolidated email report to Administrators summarizing at-risk batches and calculating total financial value at risk.
6. Allows users to mark alerts as read / dismiss them for the current day to maintain a clear workspace, while ensuring unresolved batches resurface automatically the following day.
7. Automatically updates and permanently clears alerts when batch stock reaches zero, when the batch expires, or when the batch status transitions to blocked or damaged.
8. Generates a downloadable physical warehouse guide (PDF) formatted for warehouse staff to locate, mark, or withdraw physical products from warehouse shelves.

## Why

Pharmaceutical products have rigid regulatory expiration constraints under sanitary guidelines (e.g., INVIMA, Good Distribution Practices). Batches approaching expiration without timely intervention result in severe financial losses, sanitary non-compliance, and patient safety risks. Proactive multi-tier alerts (30/60/90 days), automated administrator reports with financial valuation, and shelf-picking guides enable warehouse personnel and management to prioritize FEFO sales, negotiate vendor returns, and organize warehouse physical inventory efficiently.

---

## Acceptance Criteria

### 1. Access Control & Authorization
- Only authenticated users with the **Auxiliar de Bodega** or **Administrador** role can view expiration alerts, dismiss daily notifications, access the alerts dashboard, and download warehouse guides.
- The weekly consolidated email report is dispatched exclusively to users possessing the **Administrador** role.
- Unauthenticated access attempts to `/inventory/expiration-alerts` are redirected to the login screen.

### 2. Daily Automated Scan & Thresholds
- The system must execute an automated scan daily (scheduled at midnight `00:00`) to detect all batches matching:
  - `status = 'active'`
  - `current_quantity > 0`
  - `expiration_date >= current_date`
  - `expiration_date <= current_date + 90 days`
- Batches are segmented into three operational alert windows based on days remaining:
  - **Critical (Rojo / Red):** $\le 30$ days remaining until expiration date.
  - **High Warning (Naranja / Orange):** $31$ to $60$ days remaining until expiration date.
  - **Moderate Warning (Amarillo / Yellow):** $61$ to $90$ days remaining until expiration date.

### 3. Visual Application Notification Indicator
- A notification bell icon with an active count badge is visible in the main application navigation header across all authenticated views.
- The badge displays the total count of distinct active batches currently in expiration alert status (30, 60, or 90 days).
- If no batches are in warning status (or all active alerts have been dismissed for the current day by the user), the badge count is hidden or shows zero.
- Clicking the notification bell/indicator opens a quick preview dropdown and provides a direct navigation link to the full Expiration Alerts dashboard (`/inventory/expiration-alerts`).

### 4. Visual Urgency Color Coding
- Across both the notification dropdown and the main Expiration Alerts screen, rows and badges must apply the strict color taxonomy:
  - **Red (`#EF4444` / `bg-red-100 text-red-800`):** Products with 30 days or fewer remaining.
  - **Orange (`#F97316` / `bg-orange-100 text-orange-800`):** Products with 31 to 60 days remaining.
  - **Yellow (`#F59E0B` / `bg-yellow-100 text-yellow-800`):** Products with 61 to 90 days remaining.

### 5. Detailed Expiration Alerts Dashboard (`/inventory/expiration-alerts`)
- The detailed view presents a responsive, sortable, and filterable data table with the following mandatory columns:
  - **Medicamento:** Commercial name, generic name, concentration, and pharmaceutical form.
  - **Número de Lote:** Batch number.
  - **Existencias:** Current available physical quantity (`current_quantity`).
  - **Fecha de Vencimiento:** Exact expiration date (`YYYY-MM-DD`).
  - **Días Restantes:** Exact countdown of remaining calendar days.
  - **Nivel de Urgencia:** Colored urgency badge (30d / 60d / 90d).
  - **Valor en Riesgo:** Monetary value calculated as `current_quantity * unit_purchase_price` formatted in Colombian Pesos (COP).
- The view allows filtering by urgency tier (All, 30 days, 60 days, 90 days) and text search by medicine name or batch number.

### 6. Weekly Consolidated Administrator Email Report
- The system automatically dispatches a consolidated email report once a week (every Monday at `07:00` AM) to all active Administrators.
- The email includes:
  - Total number of batches in alert status grouped by urgency tier (30, 60, 90 days).
  - Consolidated monetary valuation of all at-risk inventory (`sum(current_quantity * unit_purchase_price)`).
  - An itemized summary table listing high-priority products to facilitate commercial clearance discounts or supplier return negotiations.

### 7. Monetary Valuation Calculation
- The financial valuation at risk for each batch and in consolidated reports is strictly calculated as:
  $$\text{Valor en Riesgo} = \text{current\_quantity} \times \text{unit\_purchase\_price}$$
- Rounded to integer currency pesos without fractional centavos.

### 8. Daily Dismissal / Mark as Read Interface Action
- The Expiration Alerts interface provides an option to **"Marcar como leídas"** or **"Limpiar notificaciones del día"**.
- Marking alerts as read dismisses the visual counter from the navigation bell for the current calendar day, allowing the user to maintain a clean workspace.
- The dismissal state is tracked per user and tied strictly to the current date (`YYYY-MM-DD`).

### 9. Next-Day Re-Alerting Guarantee
- If an alert was dismissed manually on day $D$, but the batch still has physical stock (`current_quantity > 0`) and remains within the expiration threshold on day $D+1$:
  - The system automatically restores the alert in the daily scan.
  - The notification badge count increments on day $D+1$, ensuring risk visibility is never permanently suppressed.

### 10. Automatic Permanent Removal / Resolution Conditions
- Batches are automatically and permanently removed from expiration alerts without requiring user action under any of the following conditions:
  1. **Zero Stock:** The physical balance (`current_quantity`) reaches `0` (e.g., via sales dispatch, disposal, or manual inventory adjustment).
  2. **Status Changed:** The batch status is updated to `blocked` (Bloqueado) or `damaged` (Dañado).
  3. **Expired:** The expiration date has passed (`expiration_date < current_date`), transitioning the lot into the expired quarantine workflow.
- In-memory and persisted alert tallies update immediately upon any inventory movement or status modification.

### 11. Downloadable Physical Warehouse Guide
- The Expiration Alerts dashboard contains an action button: **"Descargar Guía de Retiro / Marcación"**.
- Generates a printer-ready PDF document containing:
  - Header: Distributor details, generation timestamp, and responsible user.
  - Urgency categorization (30, 60, 90 days).
  - Physical location/warehouse details (if configured), medicine details, batch number, current quantity, expiration date, and blank checkboxes/signature fields for physical shelf auditing and product tagging.

---

## Out of Scope
- Direct automated creation of supplier return credit notes (handled in dedicated Supplier Returns module).
- Automatic incineration/disposal certificate issuance (handled in Waste & Quarantine Management).
- Push notifications via SMS or WhatsApp.
