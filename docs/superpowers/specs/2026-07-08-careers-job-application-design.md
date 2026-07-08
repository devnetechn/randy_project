# Careers / Job Application Feature

**Date:** 2026-07-08
**Status:** Approved (design)

## Summary

Add a public "Careers" page where visitors can browse open job positions and submit a job application (with resume upload), plus a new "Careers" admin tab where the owner manages positions (add/edit/close/delete) and reviews applicants (view details, download resume, update status). This is for hiring workers/contractors — not a customer-facing feature — so it is linked only from the footer, not the header nav.

## Goals

- Public `careers.php` page listing open positions, each with an "Apply" action leading to an application form.
- Application form captures: name, email, phone, position applied for, years of experience, availability, a message ("why interested"), and a required PDF resume upload.
- Admin-managed job positions (title, description, requirements, employment type, optional pay range, open/closed status) via a new admin tab — no more hardcoding positions in PHP.
- Admin can view/filter applicants, download resumes, and move each application through a status pipeline: New → Reviewed → Hired/Rejected.
- Best-effort email notification to the owner on each new application, following the existing `send_x_notification()` pattern (e.g. `send_booking_notification`).
- Footer link only ("Careers" or "Join Our Team") — not in the header nav, mirroring how the blog is intentionally excluded from primary nav.

## Non-Goals (YAGNI)

- No SMS notification for new applications — email only, per owner's choice. (Twilio SMS stays reserved for the existing CRM/lead flow.)
- No applicant-facing accounts/login, status tracking page, or email replies to applicants — this is an internal admin tool; any follow-up with the applicant happens off-platform (phone/email), same as how leads/bookings are handled today.
- No file types besides PDF for resumes — keeps upload validation simple (single MIME/extension check), matching the owner's explicit choice.
- No JS/AJAX for the public application form — plain HTML form POST to itself (like `book.php`), consistent with every other public-facing form in the codebase. AJAX is reserved for the admin panel, matching existing convention.
- No separate top-level "Positions" and "Applicants" admin tabs — both live under one "Careers" tab with an internal sub-view toggle, to avoid further crowding the admin tab bar (already 8 tabs).

## Data Model

New tables in `sql/tables.sql` (or a new `sql/migrate_careers.sql`, mirroring `migrate_crm.sql`), following the existing conventions: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`, `DATETIME` timestamps, `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`.

```sql
CREATE TABLE IF NOT EXISTS job_positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NULL,
    employment_type ENUM('full_time','part_time','contract') NOT NULL DEFAULT 'full_time',
    pay_range VARCHAR(100) NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_job_positions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position_id BIGINT UNSIGNED NULL,
    position_title_snapshot VARCHAR(150) NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    years_experience VARCHAR(50) NULL,
    availability VARCHAR(150) NULL,
    message TEXT NULL,
    resume_path VARCHAR(255) NOT NULL,
    status ENUM('new','reviewed','hired','rejected') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_job_applications_position FOREIGN KEY (position_id) REFERENCES job_positions(id) ON DELETE SET NULL,
    INDEX idx_job_applications_status (status),
    INDEX idx_job_applications_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`position_title_snapshot` is copied from the position at submit time so an application still displays a meaningful title even if the position is later deleted (FK is `ON DELETE SET NULL`, not `CASCADE` — applications are never deleted when a position goes away).

## Architecture

### Public page: `careers.php` (new)

- Follows the `contact.php`/`book.php` page structure (`includes/header.php` / `includes/footer.php`, `business_info()`, existing `.mkt`/`.section`/`.container` layout classes).
- Queries open positions directly (`SELECT * FROM job_positions WHERE status='open' ORDER BY created_at DESC`), no new `includes/careers.php` helper needed for this simple a query — matches how other simple public queries are inlined in page scripts.
- Each position rendered as a card: title, employment-type badge, pay range (if set), description, "Apply for this position" link that jumps to the form section with `?position=<id>` in the URL.
- Application form (plain `<form method="post" enctype="multipart/form-data">` posting to `careers.php` itself, matching `book.php`'s POST-to-self pattern):
  - Fields: position (`<select>`, pre-selected from `?position=` query param if present and still open), name, email, phone, years of experience, availability, message, resume file (`<input type="file" accept="application/pdf">`).
  - On `$_SERVER['REQUEST_METHOD'] === 'POST'`: validate required fields inline (name/email/phone/position required, `filter_var(...,FILTER_VALIDATE_EMAIL)`, position must exist and be `open`), validate resume upload:
    - `$_FILES['resume']` present with `UPLOAD_ERR_OK`.
    - Size cap 5MB (matches gallery upload's cap).
    - MIME check via `mime_content_type()` must resolve to `application/pdf`; reject otherwise (mirrors `api/gallery/upload.php`'s MIME-to-extension whitelist approach, but for a single type).
  - Resume stored under `uploads/resumes/` (created with `mkdir(...,0775,true)` if missing) with a randomized filename: `bin2hex(random_bytes(16)) . '.pdf'` — same collision/path-traversal-proof approach as gallery uploads.
  - On success: `INSERT INTO job_applications` (including `position_title_snapshot` copied from the position row), call a new best-effort `send_job_application_notification($application)` (added to `includes/email.php`, following the exact configured-check → `smtp_send_mail()` → `try/catch`/`error_log` pattern as `send_booking_notification`), then POST-redirect-GET back to `careers.php?applied=1`.
  - `careers.php?applied=1` renders a success banner instead of the form.

### Resume privacy: `uploads/resumes/.htaccess`

Since resumes contain PII (unlike gallery photos, which are meant to be public), add a `Deny from all` `.htaccess` file in `uploads/resumes/` so files are not directly reachable by URL even though the filename is a random 32-hex-char token. Resumes are only ever served through the admin-gated download endpoint below.

### Admin: new "Careers" tab

- `admin/index.php`: add a `<button class="tab" data-tab="careers">Careers</button>` and `<div data-panel="careers" hidden></div>`, same as every existing tab.
- `assets/js/admin.js`: new `initCareers(panel)` module registered in `MODULES`, following the `initCrm`/`initGallery` structure. Renders an internal sub-view toggle — **Positions** | **Applicants** — as two buttons at the top of the panel (client-side show/hide, no separate admin tab needed).
  - **Positions sub-view:** list of all positions (open + closed) as cards, each with Edit / Close-Reopen / Delete actions and an "Add position" button opening an inline form (title, description, requirements, employment type, pay range). Uses `api.get`/`api.post` (JSON), matching the CRM tab.
  - **Applicants sub-view:** list of applications, filterable by status and position, each row showing name/email/phone/position/status/submitted date, a "Download resume" link, and a status `<select>` (New/Reviewed/Hired/Rejected) that updates on change.

### New API endpoints (all guarded with `require_admin_api()`, JSON in/out, mirroring `api/crm/update.php`)

- `GET api/careers/positions-list.php` → `{ positions: [...] }` (all positions, any status).
- `POST api/careers/positions-save.php` → body `{ id?, title, description, requirements, employmentType, payRange, status }`; creates when `id` absent, updates (dynamic `SET`, like `api/crm/update.php`) when present. Returns `{ position }`.
- `POST api/careers/positions-delete.php` → body `{ id }`. Deletes the position (applications keep their `position_title_snapshot`). Returns `{ success: true }`.
- `GET api/careers/applications-list.php` → `{ applications: [...] }`, left-joined with `job_positions` for the current position title (falls back to `position_title_snapshot` when `position_id IS NULL`).
- `POST api/careers/applications-update.php` → body `{ id, status }`. Returns `{ application }`.
- `GET api/careers/resume-download.php?id=<application_id>` → `require_admin_api()`, looks up `resume_path` for the given application, streams the file with `Content-Disposition: attachment` and the applicant's name in the filename (e.g. `Content-Disposition: attachment; filename="Jane Doe - resume.pdf"`). 404s if the application or file doesn't exist.

### Footer link

- `includes/footer.php`: add a "Careers" link in the existing footer link list, pointing to `url('careers.php')`. No header/nav changes.

## Testing

- Manual: submit an application on `careers.php` for an open position — with a valid PDF, an oversized file, a non-PDF file, and missing required fields — confirm validation messages and that only the valid submission inserts a row and stores a resume.
- Confirm `careers.php?position=<id>` pre-selects the right position, and that a closed/invalid `position` query param falls back to the default (first open position or empty selection).
- Confirm the owner notification email fires on a successful submission (best-effort — submission still succeeds if email sending fails, matching `send_booking_notification`'s try/catch behavior).
- Confirm `uploads/resumes/` files are not directly downloadable via URL (`.htaccess` deny works), but are downloadable via the gated admin endpoint.
- In the admin Careers tab: create/edit/close/delete a position; confirm closed positions disappear from the public page but remain visible (and editable) in admin.
- Update an applicant's status through all four states and confirm the change persists on refresh.
- Confirm all new `api/careers/*` endpoints reject unauthenticated/non-admin requests (401/403), matching every other `api/**` endpoint.
