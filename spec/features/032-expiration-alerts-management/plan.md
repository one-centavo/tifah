# 032 · Expiration Alerts Management — Plan

## Approach

Implement the Expiration Alerts Management feature following the established domain-driven layered architecture: a dedicated business service (`ExpirationAlertService`), daily per-user dismissal tracking, scheduled console commands, weekly mailable reports with financial risk calculations, Livewire Volt reactive components (notification badge dropdown and alerts dashboard), and downloadable PDF warehouse picking guides.

---

### 1. Database & Schema Enhancements

- **`expiration_alert_dismissals` Table & `ExpirationAlertDismissal` Model**:
  - `id`: Primary key.
  - `user_id`: Foreign key referencing `users.id` on delete cascade.
  - `dismissed_date`: Date (`YYYY-MM-DD`) indicating the day the user marked alerts as read.
  - `created_at`, `updated_at`: Standard timestamps.
  - **Index**: Unique compound index on `['user_id', 'dismissed_date']` to prevent duplicate daily entries and enable rapid lookup.

- **`lots` & `medicines` Query Optimizations**:
  - Add composite index on `lots(status, current_quantity, expiration_date)` if not already present, ensuring instantaneous daily scans and real-time alert queries even with high batch volumes.

---

### 2. Service Layer (`App\Services\ExpirationAlertService`)

- **`getExpiringLots(int $maxDays = 90, ?string $urgencyFilter = null): Collection`**:
  - Queries active batches:
    ```php
    Lot::query()
        ->where('status', 'active')
        ->where('current_quantity', '>', 0)
        ->whereBetween('expiration_date', [Carbon::today(), Carbon::today()->addDays($maxDays)])
        ->with(['medicine.category', 'medicine.sanitaryRegistry'])
        ->orderBy('expiration_date', 'asc');
    ```
  - Appends computed attributes: `days_remaining`, `urgency_level` (`critical`, `warning`, `attention`), `monetary_risk` (`current_quantity * unit_purchase_price`).

- **`getUrgencyTier(int $daysRemaining): string`**:
  - `critical` (Red / 30d): $0 \le \text{days} \le 30$.
  - `warning` (Orange / 60d): $31 \le \text{days} \le 60$.
  - `attention` (Yellow / 90d): $61 \le \text{days} \le 90$.

- **`getAlertSummaryMetrics(): array`**:
  - Calculates:
    - `total_lots`: Total distinct batches in alert.
    - `critical_count` (<= 30d): Count of red tier lots.
    - `warning_count` (31-60d): Count of orange tier lots.
    - `attention_count` (61-90d): Count of yellow tier lots.
    - `total_monetary_risk`: Sum of `(current_quantity * unit_purchase_price)` rounded to nearest integer peso.
    - Grouped monetary breakdown by urgency tier.

- **`getUnreadAlertCountForUser(User $user): int`**:
  - Checks if a record exists in `expiration_alert_dismissals` for `user_id = $user->id` and `dismissed_date = today()`.
  - If dismissed today: returns `0`.
  - If not dismissed today: returns `total_lots` count.

- **`dismissAlertsForToday(User $user): void`**:
  - Persists a dismissal record for `$user->id` on `today()`.

- **`prepareWeeklyReportData(): array`**:
  - Assembles grouped batch summaries and financial valuations for the weekly administrator email.

---

### 3. Console Commands & Scheduling

- **`App\Console\Commands\ScanExpirationAlertsCommand` (`app:scan-expiration-alerts`)**:
  - Executes daily scan, verifies query performance, and caches summary metrics for quick badge retrieval.
  - Scheduled in `routes/console.php` daily at midnight (`00:00`).

- **`App\Console\Commands\SendWeeklyExpirationReportCommand` (`app:send-weekly-expiration-report`)**:
  - Queries active administrators (`User::where('role', 'admin')->get()`).
  - Dispatches `WeeklyExpirationReportMail` to each administrator.
  - Scheduled in `routes/console.php` every Monday at `07:00` AM.

---

### 4. Mailables & Email Templates

- **`App\Mail\WeeklyExpirationReportMail`**:
  - Formatted HTML mailable view: `resources/views/mail/weekly-expiration-report.blade.php`.
  - Includes company header, metrics dashboard, total monetary valuation at risk, and top critical batches table.

---

### 5. Livewire Volt Components & UI

- **Header Notification Badge Component (`resources/views/livewire/components/expiration-notifications-badge.blade.php`)**:
  - Placed in the global application top navigation bar.
  - Listens to Livewire events (`expiration-alerts-updated`, `lot-quantity-changed`).
  - Displays reactive bell icon with colored badge counter.
  - Dropdown menu previewing the top 5 urgent expiring batches with direct link to full view.

- **Main Expiration Alerts View (`resources/views/livewire/inventory/expiration-alerts.blade.php`)**:
  - Accessible at route `/inventory/expiration-alerts`.
  - Top summary metric cards: Total Batches, Critical (30d), High (60d), Moderate (90d), and Total Value at Risk (COP).
  - Search input (Medicine name, Batch number) and urgency filter buttons.
  - Table with color-coded badges, formatted dates, countdowns, stock counts, and financial values.
  - Header actions:
    - **"Limpiar Notificaciones del Día"** (Triggers `dismissAlertsForToday`).
    - **"Descargar Guía de Retiro / Marcación"** (Downloads physical guide PDF).

---

### 6. PDF Guide Generation (`App\Http\Controllers\ExpirationAlertPdfController`)

- **View Template (`resources/views/pdf/expiration-guide.blade.php`)**:
  - Standardized physical warehouse audit layout.
  - Groups batches by urgency tier.
  - Includes columns for Medicine, Batch Number, Physical Stock, Expiry Date, Location/Shelf, and Blank Verification Checkbox / Operator Signature area.
- Registered under `GET /inventory/expiration-alerts/pdf` with `auth` middleware.

---

### 7. Real-Time Synchronization & Automatic Resolution

- Whenever batch stock decreases to zero (`current_quantity = 0`), the batch status transitions to `blocked`/`damaged`, or an invoice dispatches stock:
  - The query conditions (`status = 'active'`, `current_quantity > 0`, `expiration_date >= today`) automatically exclude the batch.
  - Livewire components re-render seamlessly without requiring background cache purging.

---

### 8. Testing & Quality Assurance

- **Unit Tests (`tests/Unit/Services/ExpirationAlertServiceTest.php`)**:
  - Threshold classification (30d, 60d, 90d, safe >90d, expired <0d).
  - Financial risk calculations with integer rounding.
  - Daily dismissal tracking and next-day re-alerting reset.
- **Feature Tests (`tests/Feature/Inventory/ExpirationAlertsTest.php`)**:
  - Access control for warehouse assistants and administrators.
  - Livewire notifications badge rendering and interaction.
  - Alerts dashboard searching, filtering, and daily dismiss action.
  - Console commands execution (`app:scan-expiration-alerts` and `app:send-weekly-expiration-report`).
  - Weekly report mailable rendering and email delivery assertion.
  - PDF warehouse guide download response verification.
