# K-Elec Warranty Management System

Production-ready Phase 1 foundation for the K-Elec Warranty Management System built with Laravel 12, Blade, Tailwind CSS, Alpine.js, Laravel Breeze, and Spatie Permission.

## Requirements

- PHP 8.2+ (8.3+ recommended)
- Composer
- Node.js 18+
- MySQL 8+ (SQLite supported for local/testing)
- Queue worker process
- Cron for Laravel Scheduler

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure database credentials in `.env`, then:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

## Default administrator accounts

| Role | Email | Password |
|------|-------|----------|
| Super Administrator | admin@kelec.test | password |
| Warranty Administrator | warranty@kelec.test | password |
| Customer Support | support@kelec.test | password |

Change these credentials immediately in non-local environments.

## Local development

```bash
composer run dev
```

Or separately:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Important routes

### Public

- `/` Home
- `/register-warranty` Multi-step registration wizard
- `/warranty-lookup` Secure warranty lookup
- `/privacy-policy`
- `/warranty-terms`

### Admin

- `/admin/dashboard`
- `/admin/warranties`
- `/admin/warranties/pending`
- `/admin/customers`
- `/admin/products`
- `/admin/dealers`
- `/admin/settings`
- `/login`

## Environment configuration

See `.env.example` for:

- Application timezone (`APP_TIMEZONE=Africa/Nairobi`)
- Database
- Queue (`QUEUE_CONNECTION=database`)
- AWS SES SMTP mail settings
- SMS endpoint placeholders
- Odoo API placeholders and mock mode flags

Sensitive Odoo/SMS secrets can also be stored encrypted through **Admin → Settings**.

## Queue and scheduler

Queue worker:

```bash
php artisan queue:work --tries=3
```

Scheduler cron entry:

```cron
* * * * * cd /path/to/kelec-warranties && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:

- `warranties:mark-expired`
- `odoo:retry-failed-validations`
- `notifications:retry-failed`
- `odoo:import-pos-sales`

## AWS SES SMTP

Set:

```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.<region>.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-ses-smtp-username
MAIL_PASSWORD=your-ses-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=warranties@yourdomain.com
MAIL_FROM_NAME="K-Elec Warranties"
```

## SMS endpoint

Configure in Admin Settings or environment-equivalent settings:

- Endpoint URL
- HTTP method
- API key
- Sender ID
- Auth header
- Phone/message parameter names
- Timeout / enabled flag

When `sms_enabled` is false, SMS is logged in mock mode for safe local testing.

## Brand Shop POS automation

Configured Brand Shop branches (default): `Sarin,CBD` via setting `pos_brand_shop_branches`.

Secure webhook:

```http
POST /api/odoo/pos-sale
Authorization: Bearer <INTEGRATION_API_TOKEN>
```

Payload example:

```json
{
  "serial_number": "KE123",
  "branch_name": "Sarin",
  "odoo_pos_order_id": "POS-1001",
  "full_name": "Jane Doe",
  "mobile_number": "0712345678",
  "purchase_date": "2026-07-27",
  "marketing_consent": false
}
```

Scheduler also runs `odoo:import-pos-sales` every 15 minutes (mock-capable).

POS warranties never auto-grant marketing consent. Customers receive an optional `/consent/{token}` opt-in link. Incomplete customer details create a provisional warranty with `/complete-registration/{token}`.

## Testing

```bash
php artisan test
```

External Odoo/SMS/SES calls are mocked or disabled during automated tests.

## Deployment notes

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure MySQL and run `php artisan migrate --force`
3. Seed only on first setup if required
4. Run `php artisan config:cache`, `route:cache`, `view:cache`
5. Start queue worker under Supervisor/systemd
6. Configure scheduler cron
7. Enforce HTTPS
8. Ensure `storage/` and `bootstrap/cache/` are writable
9. Keep receipt files on the private `local` disk; serve only through authorised routes
10. Rotate logs and back up the database regularly

## Phase 1 scope complete

- Public registration wizard and lookup
- Consent capture (privacy required, marketing optional)
- Admin dashboard and CRUD modules
- Pending verification / approve / reject baseline
- Roles and permissions
- Notification queue + logs
- Audit logs
- Odoo mock validation path and Brand Shop POS webhook import (Sarin/CBD)
- Seeders, factories, and feature tests

Commercial brief scope for registration, manual verification, notifications, admin management, and POS automation is covered. Live Odoo field mappings still depend on credentials/documentation from the client Odoo administrator.
