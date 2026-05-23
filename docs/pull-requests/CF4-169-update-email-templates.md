# 📦 Pull Request

## 📋 Description

Standardizes the look and feel of all transactional emails using one shared Blade layout. Every customer and admin email now shares the same Ciclo Finca 4 branding (colors, header, footer, buttons) so messages feel consistent and trustworthy.

This PR does **not** change order logic, notifications triggers, or performance-related settings (Unlighthouse, WebP/AVIF, Font Awesome subset, deferred scripts, hero assets, etc.).

---

## 🎯 User Story

**Related US:** CF4-169

**US description:**
> As a customer receiving system emails, I want all emails to have a consistent and professional design, so I can easily recognize store messages and trust them.

---

## 🧩 Changes made

### ✨ New feature

- Added reusable base email layout: `resources/views/emails/layouts/base.blade.php`
  - Brand header: logo (top-left), centered **CICLO FINCA 4** wordmark (same colors as the site navbar), optional email title/subtitle below
  - Soft background `#DAF1DE`, primary buttons `#235347`
  - Shared footer with contact email and website link
  - Table-based structure for better support in Gmail, Outlook, and Apple Mail
- Added reusable CTA button partial: `resources/views/emails/partials/button.blade.php`
- Added product review email view: `resources/views/emails/product-review-reminder.blade.php`
- Added **local HTML preview generator** (no SMTP needed): `tests/Feature/GenerateEmailPreviewHtmlTest.php`
  - Writes all templates to `storage/app/email-previews/` with an `index.html` menu

### 🐛 Bug fix

- Fixed logo appearing too small in emails (CSS was overriding image dimensions)
- Fixed logo not loading in offline HTML previews (absolute URLs replaced with local relative paths when generating previews)

### ♻️ Refactor

- Migrated all existing email views to extend the base layout:
  - `order-expiry-reminder.blade.php`
  - `weekly-dashboard-report.blade.php`
  - `order-ready-to-pickup.blade.php`
  - `order-completed.blade.php`
  - `order-cancelled-notification.blade.php`
- Updated `ProductReviewReminderNotification` to use the new Blade view instead of plain text lines

### 🧪 Tests added or updated

- `tests/Feature/CF4169TransactionalEmailLayoutTest.php` — verifies shared layout markers on all transactional emails
- `tests/Feature/GenerateEmailPreviewHtmlTest.php` — generates static HTML previews (runs only when `GENERATE_EMAIL_PREVIEWS=1`)

### 📚 Documentation updated

- Preview usage documented in test file docblocks
- This PR description for QA

---

## 🏗 Affected modules or components

| Area | Affected? |
| ------ | ----------- |
| Backend (Laravel) | ☑ |
| Frontend | ☐ |
| Database | ☐ |
| API | ☐ |
| Other | ☑ (email Blade views only) |

**Relevant files:**

- `resources/views/emails/layouts/base.blade.php`
- `resources/views/emails/partials/button.blade.php`
- `resources/views/emails/*.blade.php`
- `app/Notifications/ProductReviewReminderNotification.php`
- `tests/Feature/CF4169TransactionalEmailLayoutTest.php`
- `tests/Feature/GenerateEmailPreviewHtmlTest.php`
- `.gitignore` (ignores generated preview folder)

---

## ⚙️ How to test this change

### Option A — Easiest: open all templates in the browser (recommended, no email account)

This is the recommended path for QA.

1. In the project folder, run:

   ```bash
   GENERATE_EMAIL_PREVIEWS=1 php artisan test tests/Feature/GenerateEmailPreviewHtmlTest.php
   ```

   **Docker:**

   ```bash
   docker exec laravel_app_ciclo env GENERATE_EMAIL_PREVIEWS=1 php artisan test tests/Feature/GenerateEmailPreviewHtmlTest.php
   ```

2. Open this file in your browser (double-click or drag into Chrome/Firefox):

   `storage/app/email-previews/index.html`

3. From the index page, open **each link** (9 emails). For every template, confirm:

   - Green header with logo on the **top-left**
   - **CICLO FINCA 4** centered in the header (CICLO/4 light green, FINCA accent green)
   - Email-specific title under the brand when applicable (e.g. “Order ready for pickup”, “Order reminder”)
   - Light green page background around the white email card
   - Green action buttons where present
   - Footer shows contact email and website
   - Text is readable; no broken layout on a narrow/mobile window

4. **Emails to review from the index:**

   | Preview file | What it represents |
   |--------------|-------------------|
   | 01-order-expiry-reminder | Order expiry reminder |
   | 02-weekly-dashboard-report | Weekly admin dashboard report |
   | 03-order-ready-to-pickup | Order ready for pickup |
   | 04-order-completed | Order completed |
   | 05-order-cancelled-notification | Order cancelled |
   | 06-product-review-reminder | Product review request |
   | 07–09 | Same order notifications sent via the notification system |

### Option B — Automated check (developers / CI)

```bash
php artisan test tests/Feature/CF4169TransactionalEmailLayoutTest.php
```

### Option C — Optional: real emails on staging

Only if you already have mail configured on staging:

1. Trigger one order status change (ready for pickup, completed, cancelled) and check the inbox.
2. Run the weekly report command or wait for its schedule.
3. Confirm the received email matches the preview styling.

### Expected result

- All transactional emails look visually consistent with Ciclo Finca 4 branding.
- Header, colors, buttons, and footer match across templates.
- Dynamic content (order number, products, totals, dates) still appears correctly in previews and real emails.
- No changes to storefront pages, admin UI, or order workflows beyond email appearance.

---

## 📸 Evidence

| # | Description | Image / link |
|---|-------------|----------------|
| 1 | Index page listing all email previews | Generate with Option A, then attach screenshot of `storage/app/email-previews/index.html` |
| 2 | Sample customer email (e.g. order ready for pickup) | Screenshot from preview `03-order-ready-to-pickup.html` |
| 3 | Weekly dashboard report email | Screenshot from preview `02-weekly-dashboard-report.html` |

**Security:** No secrets added. Generated preview files are gitignored under `storage/app/email-previews/`.

---

## 🧪 Testing performed

- [x] Manual testing (HTML previews in browser)
- [x] Unit tests (`CF4169TransactionalEmailLayoutTest`)
- [x] Integration tests (preview generator test)
- [ ] Staging environment testing *(QA optional — Option C)*

---

## ⚠️ Risks or impacts

- [ ] Performance impact
- [ ] Database changes
- [ ] Possible conflict with other modules
                       
- [x] None — email presentation only; triggers and business logic unchanged

---

## 📌 Pre-merge checklist

- [x] Code follows project conventions
- [x] No console errors (email HTML only)
- [x] Tests updated if applicable
- [ ] Build verified *(N/A — no frontend build changes)*
- [ ] User story linked in Jira (CF4-169)
- [x] No secrets or sensitive data in code

---

## 👀 Suggested reviewer

@Darwin-Nunez-10

---

## 📅 Sprint

**Sprint:** Sprint 5
