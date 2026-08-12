# 032 · Expiration Alerts Management — Tasks

## 1. Database & Migrations
- [ ] Create migration for `expiration_alert_dismissals` table (`user_id`, `dismissed_date`, timestamps, unique composite index on `['user_id', 'dismissed_date']`).
- [ ] Create Eloquent model `App\Models\ExpirationAlertDismissal` with relationships to `User`.
- [ ] Verify/add database indexes on `lots` table for `status`, `current_quantity`, and `expiration_date`.

## 2. Business Logic & Service Layer
- [ ] Create `App\Services\ExpirationAlertService`.
- [ ] Implement `getExpiringLots()` filtering active, positive-stock batches with expiration dates within 30, 60, and 90 days.
- [ ] Implement `getUrgencyTier()` mapping remaining days to `critical` (<=30d / Red), `warning` (31-60d / Orange), and `attention` (61-90d / Yellow).
- [ ] Implement `getAlertSummaryMetrics()` computing lot counts and total monetary valuation (`current_quantity * unit_purchase_price`) with integer rounding.
- [ ] Implement `getUnreadAlertCountForUser()` evaluating user's daily dismissal status.
- [ ] Implement `dismissAlertsForToday()` persisting the user's daily dismissal record.
- [ ] Implement automatic exclusion logic for depleted (`current_quantity = 0`), expired, blocked, or damaged lots.

## 3. Console Commands & Scheduling
- [ ] Create `App\Console\Commands\ScanExpirationAlertsCommand` (`app:scan-expiration-alerts`).
- [ ] Create `App\Console\Commands\SendWeeklyExpirationReportCommand` (`app:send-weekly-expiration-report`).
- [ ] Register daily scan (`00:00`) and weekly report (`Mondays 07:00`) in `routes/console.php`.

## 4. Mailables & Email Templates
- [ ] Create Mailable `App\Mail\WeeklyExpirationReportMail`.
- [ ] Create Blade email template `resources/views/mail/weekly-expiration-report.blade.php` displaying total monetary risk and prioritized expiring batches table.

## 5. Livewire Volt Components & UI
- [ ] Create `resources/views/livewire/components/expiration-notifications-badge.blade.php` with reactive bell icon, counter badge, and dropdown preview.
- [ ] Integrate notification badge into main application navigation header layout.
- [ ] Create `resources/views/livewire/inventory/expiration-alerts.blade.php` dashboard component.
- [ ] Implement summary cards (Total Batches, 30d Critical, 60d Warning, 90d Attention, Total Monetary Value).
- [ ] Implement urgency filter tabs (All, 30 Days, 60 Days, 90 Days) and search by medicine/batch.
- [ ] Implement color-coded badges and rows according to urgency rules (Red, Orange, Yellow).
- [ ] Implement "Limpiar Notificaciones del Día" action button with immediate badge update.

## 6. PDF Guide Generation
- [ ] Create Blade PDF template `resources/views/pdf/expiration-guide.blade.php` for warehouse picking/marking guide.
- [ ] Create controller `App\Http\Controllers\ExpirationAlertPdfController` to stream/download the generated PDF.
- [ ] Add "Descargar Guía de Retiro / Marcación" button in the alerts dashboard.

## 7. Routing & Navigation
- [ ] Register `/inventory/expiration-alerts` route under `auth` middleware in `routes/web.php`.
- [ ] Register `/inventory/expiration-alerts/pdf` export route under `auth` middleware.
- [ ] Add Expiration Alerts navigation item in the main inventory navigation menu.

## 8. Testing & Quality Assurance
- [ ] Write unit tests for `ExpirationAlertService` (30/60/90 days classification, monetary valuation calculation, daily dismissal reset).
- [ ] Write feature tests for navigation notification badge and dropdown behavior.
- [ ] Write feature tests for `/inventory/expiration-alerts` view filtering, search, and daily dismissal action.
- [ ] Write feature tests for daily scan command and weekly administrator email dispatch.
- [ ] Write feature tests for physical warehouse guide PDF generation.
- [ ] Run `docker compose exec tifah-app php artisan test` to verify all tests pass without regressions.
- [ ] Update `spec/constitution/roadmap.md` to reflect the feature in the roadmap.
