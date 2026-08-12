# 032 · Expiration Alerts Management — Tasks

## 1. Database & Migrations
- [x] Create migration for `expiration_alert_dismissals` table (`user_id`, `dismissed_date`, timestamps, unique composite index on `['user_id', 'dismissed_date']`).
- [x] Create Eloquent model `App\Models\ExpirationAlertDismissal` with relationships to `User`.
- [x] Verify/add database indexes on `lots` table for `status`, `current_quantity`, and `expiration_date`.

## 2. Business Logic & Service Layer
- [x] Create `App\Services\ExpirationAlertService`.
- [x] Implement `getExpiringLots()` filtering active, positive-stock batches with expiration dates within 30, 60, and 90 days.
- [x] Implement `getUrgencyTier()` mapping remaining days to `critical` (<=30d / Red), `warning` (31-60d / Orange), and `attention` (61-90d / Yellow).
- [x] Implement `getAlertSummaryMetrics()` computing lot counts and total monetary valuation (`current_quantity * unit_purchase_price`) with integer rounding.
- [x] Implement `getUnreadAlertCountForUser()` evaluating user's daily dismissal status.
- [x] Implement `dismissAlertsForToday()` persisting the user's daily dismissal record.
- [x] Implement automatic exclusion logic for depleted (`current_quantity = 0`), expired, blocked, or damaged lots.

## 3. Console Commands & Scheduling
- [x] Create `App\Console\Commands\ScanExpirationAlertsCommand` (`app:scan-expiration-alerts`).
- [x] Create `App\Console\Commands\SendWeeklyExpirationReportCommand` (`app:send-weekly-expiration-report`).
- [x] Register daily scan (`00:00`) and weekly report (`Mondays 07:00`) in `routes/console.php`.

## 4. Mailables & Email Templates
- [x] Create Mailable `App\Mail\WeeklyExpirationReportMail`.
- [x] Create Blade email template `resources/views/mail/weekly-expiration-report.blade.php` displaying total monetary risk and prioritized expiring batches table.

## 5. Livewire Volt Components & UI
- [x] Create `resources/views/livewire/components/expiration-notifications-badge.blade.php` with reactive bell icon, counter badge, and dropdown preview.
- [x] Integrate notification badge into main application navigation header layout.
- [x] Create `resources/views/livewire/inventory/expiration-alerts.blade.php` dashboard component.
- [x] Implement summary cards (Total Batches, 30d Critical, 60d Warning, 90d Attention, Total Monetary Value).
- [x] Implement urgency filter tabs (All, 30 Days, 60 Days, 90 Days) and search by medicine/batch.
- [x] Implement color-coded badges and rows according to urgency rules (Red, Orange, Yellow).
- [x] Implement "Limpiar Notificaciones del Día" action button with immediate badge update.

## 6. PDF Guide Generation
- [x] Create Blade PDF template `resources/views/pdf/expiration-guide.blade.php` for warehouse picking/marking guide.
- [x] Create controller `App\Http\Controllers\ExpirationAlertPdfController` to stream/download the generated PDF.
- [x] Add "Descargar Guía de Retiro / Marcación" button in the alerts dashboard.

## 7. Routing & Navigation
- [x] Register `/inventory/expiration-alerts` route under `auth` middleware in `routes/web.php`.
- [x] Register `/inventory/expiration-alerts/pdf` export route under `auth` middleware.
- [x] Add Expiration Alerts navigation item in the main inventory navigation menu.

## 8. Testing & Quality Assurance
- [x] Write unit tests for `ExpirationAlertService` (30/60/90 days classification, monetary valuation calculation, daily dismissal reset).
- [x] Write feature tests for navigation notification badge and dropdown behavior.
- [x] Write feature tests for `/inventory/expiration-alerts` view filtering, search, and daily dismissal action.
- [x] Write feature tests for daily scan command and weekly administrator email dispatch.
- [x] Write feature tests for physical warehouse guide PDF generation.
- [x] Run `docker compose exec tifah-app php artisan test` to verify all tests pass without regressions.
- [x] Update `spec/constitution/roadmap.md` to reflect the feature in the roadmap.
