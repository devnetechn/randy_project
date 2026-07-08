# Careers / Job Application Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a public `careers.php` page where visitors browse open job positions and submit a job application (with a PDF resume upload), plus a new "Careers" admin tab where the owner manages positions (add/edit/close/delete) and reviews applicants (view, download resume, update status New → Reviewed → Hired/Rejected).

**Architecture:** Two new tables (`job_positions`, `job_applications`). A public page (`careers.php`) follows the exact plain-POST-to-self pattern already used by `book.php` — no JS/AJAX. A new best-effort `send_job_application_notification()` in `includes/email.php` follows the exact pattern of `send_booking_notification()`. Six new admin-only JSON API endpoints under `api/careers/*` (guarded with `require_admin_api()`) follow the exact shape of `api/crm/update.php` and `api/gallery/upload.php`. A new `initCareers()` admin JS module (with an internal Positions/Applicants sub-view toggle) is registered into the existing `MODULES` map in `assets/js/admin.js`, following `initGallery()` (CRUD + inline edit form) and `initCrm()` (status dropdown + filter tabs). Resumes are stored under `uploads/resumes/`, blocked from direct web access via `.htaccess`, and served only through an admin-gated download endpoint.

**Tech Stack:** Vanilla PHP 8 + PDO/MySQL (XAMPP), plain JS (no framework, no build step), `assets/css/styles.css`. **No test framework exists** — verification is via `php -l` (lint), short PHP CLI scripts run with `C:\xampp\php\php.exe`, HTTP/browser checks against `http://localhost/randy/...`, and admin login for manual UI verification.

**Conventions to follow:**
- Admin API endpoints: `require_once __DIR__ . '/../../includes/app.php';` then `require_admin_api();` before touching the DB. Returns via `json_out($data)` / `json_error($msg, $status)`. Non-GET endpoints check `$_SERVER['REQUEST_METHOD'] !== 'POST'` first and reject with 405.
- Admin JS modules: one `init<Name>(panel)` function per tab, registered in the `MODULES` map at the bottom of `assets/js/admin.js`; panels are lazy-loaded on first tab click and support a `refresh.<name>()` re-fetch on subsequent clicks.
- Globals available inside `admin.js` functions (defined in `assets/js/app.js`): `api.get(path)`, `api.post(path, obj)`, `api.upload(path, formData)`, `api.url(path)`, `escapeHtml(str)`, `toast(msg, type)`, `cap(str)`, `fmt(dt)`.
- Public forms: plain `<form method="post">` posting to the page itself, inline `$_SERVER['REQUEST_METHOD'] === 'POST'` validation building an `$error` string, `redirect('page.php?flag=1')` on success (POST-redirect-GET). See `book.php`.
- File uploads: random filename (`bin2hex(random_bytes(16)) . $ext`), MIME-checked via `mime_content_type()`, 5MB cap, stored under `uploads/<thing>/`, directory created with `mkdir(...,0775,true)` if missing. See `api/gallery/upload.php`.
- Email: every notification function is best-effort — checks `email_is_configured()`, then `try { smtp_send_mail(...) } catch (Throwable $e) { error_log(...) }`, never throws. See `send_booking_notification()` in `includes/email.php`.
- New tables go straight into `sql/tables.sql` as `CREATE TABLE IF NOT EXISTS` — `setup.php` re-runs this whole file on every visit, so no separate migration file is needed for brand-new tables (only `ALTER TABLE` changes to *existing* tables need the `migrate_*.sql` + `setup.php` column-check pattern).
- Commit after each task. Work happens on branch `feature/careers-job-application` (created in Task 0).

---

## File Structure

| File | Responsibility | Action |
|------|----------------|--------|
| `sql/tables.sql` | `job_positions`, `job_applications` table definitions | Modify |
| `uploads/resumes/.htaccess` | Block direct web access to uploaded resumes (PII) | Create |
| `includes/email.php` | `send_job_application_notification()` | Modify |
| `careers.php` | Public positions listing + application form + submit handling | Create |
| `api/careers/positions-list.php` | Admin: list all positions | Create |
| `api/careers/positions-save.php` | Admin: create/update a position | Create |
| `api/careers/positions-delete.php` | Admin: delete a position | Create |
| `api/careers/applications-list.php` | Admin: list all applications | Create |
| `api/careers/applications-update.php` | Admin: update an application's status | Create |
| `api/careers/resume-download.php` | Admin: stream a resume PDF | Create |
| `admin/index.php` | "Careers" tab button + panel `<div>` | Modify |
| `assets/js/admin.js` | `initCareers()` — Positions/Applicants sub-views, registered in `MODULES` | Modify |
| `assets/css/styles.css` | `.badge--new/reviewed/hired/rejected` | Modify |
| `includes/footer.php` | "Careers" link in the Company footer column | Modify |

---

## Task 0: Create the feature branch

- [ ] **Step 1: Create and switch to the branch**

```bash
git checkout main
git pull
git checkout -b feature/careers-job-application
```

(This project is a live XAMPP site served directly from `C:\xampp\htdocs\randy`, so work happens on a regular branch checked out in place, not a worktree, so pages remain testable at `http://localhost/randy/...` throughout.)

---

## Task 1: Database schema (`job_positions`, `job_applications`)

**Files:**
- Modify: `sql/tables.sql` (append at the end, after the `settings` table)

- [ ] **Step 1: Append the new tables**

At the end of `sql/tables.sql`, after the `settings` table definition, add:

```sql

CREATE TABLE IF NOT EXISTS job_positions (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(150) NOT NULL,
  description      TEXT NOT NULL,
  requirements     TEXT NULL,
  employment_type  ENUM('full_time','part_time','contract') NOT NULL DEFAULT 'full_time',
  pay_range        VARCHAR(100) NULL,
  status           ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_job_positions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_applications (
  id                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id              BIGINT UNSIGNED NULL,
  position_title_snapshot  VARCHAR(150) NOT NULL,
  name                     VARCHAR(150) NOT NULL,
  email                    VARCHAR(190) NOT NULL,
  phone                    VARCHAR(30) NOT NULL,
  years_experience         VARCHAR(50) NULL,
  availability             VARCHAR(150) NULL,
  message                  TEXT NULL,
  resume_path              VARCHAR(255) NOT NULL,
  status                   ENUM('new','reviewed','hired','rejected') NOT NULL DEFAULT 'new',
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_job_applications_position FOREIGN KEY (position_id) REFERENCES job_positions(id) ON DELETE SET NULL,
  INDEX idx_job_applications_status (status),
  INDEX idx_job_applications_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Apply the schema via setup.php**

Open `http://localhost/randy/setup.php` in a browser (or `curl`). Confirm the output includes "Tables created (or already present)." and no errors.

- [ ] **Step 3: Verify the tables exist and accept a round-trip row**

```
C:\xampp\php\php.exe -r "require 'includes/db.php'; db()->exec(\"INSERT INTO job_positions (title, description, employment_type) VALUES ('Test Position', 'Test description', 'full_time')\"); $id = db()->lastInsertId(); $row = db()->query('SELECT * FROM job_positions WHERE id = ' . $id)->fetch(); echo json_encode($row) . PHP_EOL; db()->exec('DELETE FROM job_positions WHERE id = ' . $id); echo 'cleaned up' . PHP_EOL;"
```

Expected: one JSON line showing the inserted row (with `status: "open"` as the default), then `cleaned up`, no PDO errors.

- [ ] **Step 4: Commit**

```bash
git add sql/tables.sql
git commit -m "feat(db): add job_positions and job_applications tables"
```

---

## Task 2: Protect the resumes upload folder

**Files:**
- Create: `uploads/resumes/.htaccess`

- [ ] **Step 1: Create the directory and the deny-all `.htaccess`**

Resumes contain personal information (unlike gallery photos, which are meant to be public), so block all direct HTTP access to this folder — resumes will only ever be served through the admin-gated `api/careers/resume-download.php` endpoint (Task 6).

```bash
mkdir -p uploads/resumes
```

Create `uploads/resumes/.htaccess`:

```
Require all denied
```

- [ ] **Step 2: Verify direct access is blocked**

Drop a throwaway file and request it directly:

```bash
echo "test" > uploads/resumes/test.txt
```

```powershell
try { Invoke-WebRequest "http://localhost/randy/uploads/resumes/test.txt" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```

Expected: `403`. Then remove the throwaway file:

```bash
rm uploads/resumes/test.txt
```

- [ ] **Step 3: Commit**

```bash
git add uploads/resumes/.htaccess
git commit -m "feat(careers): block direct access to uploaded resumes"
```

(Note: if `uploads/resumes/` ends up empty except for `.htaccess`, git will track it fine since `.htaccess` is a real tracked file.)

---

## Task 3: Job application email notification

**Files:**
- Modify: `includes/email.php` (append at the end of the file)

- [ ] **Step 1: Add `send_job_application_notification()`**

At the end of `includes/email.php`, after the closing `}` of `send_crm_stage_email()`, add:

```php

/**
 * Send a "new job application" alert. Best-effort: logs and returns on any
 * problem, never throws, so the application flow is unaffected.
 */
function send_job_application_notification(array $application): void
{
    if (!email_is_configured()) {
        error_log('[email] not configured — skipping job application notification for #' . ($application['id'] ?? '?'));
        return;
    }

    $cfg = config('email');
    $to = $cfg['to'] ?: $cfg['user'];

    try {
        $b = business_info();
        $subject = 'New job application — ' . $application['position_title_snapshot'];
        $body = implode("\r\n", [
            'A new job application was submitted on ' . $b['name'] . '.',
            '',
            'Applicant:  ' . $application['name'],
            'Email:      ' . $application['email'],
            'Phone:      ' . $application['phone'],
            'Position:   ' . $application['position_title_snapshot'],
            'Experience: ' . ($application['years_experience'] ?: '—'),
            'Available:  ' . ($application['availability'] ?: '—'),
            'Message:    ' . ($application['message'] ?: '—'),
            '',
            'Application #' . $application['id'] . ' — view and download the resume in the admin dashboard (Careers tab).',
        ]);

        smtp_send_mail($cfg, $to, $subject, $body);
        error_log('[email] job application notification sent for #' . $application['id']);
    } catch (Throwable $e) {
        error_log('[email] job application notification failed for #' . ($application['id'] ?? '?') . ': ' . $e->getMessage());
    }
}
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/email.php`
Expected: `No syntax errors detected in includes/email.php`

- [ ] **Step 3: Commit**

```bash
git add includes/email.php
git commit -m "feat(email): add job application notification"
```

---

## Task 4: Public Careers page (`careers.php`)

**Files:**
- Create: `careers.php`

- [ ] **Step 1: Create the page**

Create `careers.php`:

```php
<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/marketing.php';

$positions = db()->query("SELECT * FROM job_positions WHERE status = 'open' ORDER BY created_at DESC")->fetchAll();

$EMPLOYMENT_LABELS = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'];

$error = null;
$sent = isset($_GET['applied']);
$selectedPosition = (int) ($_GET['position'] ?? 0);
$form = [
    'position_id' => $selectedPosition,
    'name' => '', 'email' => '', 'phone' => '',
    'years_experience' => '', 'availability' => '', 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['position_id']      = (int) ($_POST['position_id'] ?? 0);
    $form['name']             = trim($_POST['name'] ?? '');
    $form['email']            = trim($_POST['email'] ?? '');
    $form['phone']            = trim($_POST['phone'] ?? '');
    $form['years_experience'] = trim($_POST['years_experience'] ?? '');
    $form['availability']     = trim($_POST['availability'] ?? '');
    $form['message']          = trim($_POST['message'] ?? '');

    $position = null;
    foreach ($positions as $p) {
        if ((int) $p['id'] === $form['position_id']) { $position = $p; break; }
    }

    if (!$position) {
        $error = 'Please choose a valid open position.';
    } elseif ($form['name'] === '' || $form['email'] === '' || $form['phone'] === '') {
        $error = 'Please provide your name, email, and phone.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please attach your resume as a PDF.';
    } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
        $error = 'Resume must be 5 MB or smaller.';
    } elseif (mime_content_type($_FILES['resume']['tmp_name']) !== 'application/pdf') {
        $error = 'Resume must be a PDF file.';
    } else {
        $dir = __DIR__ . '/uploads/resumes';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $filename = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $dir . '/' . $filename)) {
            $error = 'Could not save your resume — please try again.';
        } else {
            db()->prepare(
                'INSERT INTO job_applications
                    (position_id, position_title_snapshot, name, email, phone, years_experience, availability, message, resume_path)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $position['id'], $position['title'], $form['name'], $form['email'], $form['phone'],
                $form['years_experience'] ?: null, $form['availability'] ?: null, $form['message'] ?: null,
                $filename,
            ]);

            require_once __DIR__ . '/includes/email.php';
            $id = (int) db()->lastInsertId();
            $st = db()->prepare('SELECT * FROM job_applications WHERE id = ?');
            $st->execute([$id]);
            if ($application = $st->fetch()) {
                send_job_application_notification($application);
            }

            redirect('careers.php?applied=1');
        }
    }
}

$page_title = 'Careers — Join Our Team';
require __DIR__ . '/includes/header.php';
?>
<div class="mkt">
    <section class="page-hero">
        <div class="page-hero__bg" aria-hidden="true"></div>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a><span>/</span> Careers</nav>
            <span class="eyebrow">Join our team</span>
            <h1 style="margin-top:1rem">Build your career with <span class="ul-brush">Randy's</span>.</h1>
            <p>We're always looking for skilled, reliable people to join our painting and drywall crew across the Lehigh Valley and Bucks County, PA.</p>
        </div>
    </section>

    <?php if ($sent): ?>
    <section class="section section--tight">
        <div class="container">
            <div class="form-card" style="max-width:36rem;margin-inline:auto;text-align:center">
                <h2>Thanks for applying!</h2>
                <p style="color:var(--muted);margin-top:.5rem">We've received your application and will reach out if it's a good fit.</p>
                <p style="margin-top:1.5rem"><a href="<?= e(url('careers.php')) ?>">&larr; Back to open positions</a></p>
            </div>
        </div>
    </section>
    <?php else: ?>

    <section class="section section--tight">
        <div class="container">
            <?php if (!$positions): ?>
                <p style="color:var(--muted);text-align:center">No open positions right now — please check back soon.</p>
            <?php else: ?>
            <div class="services-grid">
                <?php foreach ($positions as $p): ?>
                <div class="service-card">
                    <h3><?= e($p['title']) ?></h3>
                    <p style="color:var(--muted);font-size:.85rem;margin-bottom:.5rem"><?= e($EMPLOYMENT_LABELS[$p['employment_type']] ?? $p['employment_type']) ?><?= $p['pay_range'] ? ' · ' . e($p['pay_range']) : '' ?></p>
                    <p><?= nl2br(e($p['description'])) ?></p>
                    <?php if ($p['requirements']): ?><p style="margin-top:.75rem"><strong>Requirements:</strong><br><?= nl2br(e($p['requirements'])) ?></p><?php endif; ?>
                    <p style="margin-top:1rem"><a class="textlink" href="<?= e(url('careers.php?position=' . $p['id'])) ?>#apply">Apply for this position<?= svg_arrow() ?></a></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($positions): ?>
    <section class="section section--tight" id="apply">
        <div class="container">
            <div class="form-card" style="max-width:36rem;margin-inline:auto">
                <h2 style="margin-bottom:1.5rem">Apply now</h2>
                <form method="post" enctype="multipart/form-data" novalidate>
                    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
                    <label class="field"><span>Position</span>
                        <select name="position_id" required>
                            <?php foreach ($positions as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"<?= $p['id'] === $form['position_id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field"><span>Your name</span>
                        <input type="text" name="name" value="<?= e($form['name']) ?>" required>
                    </label>
                    <label class="field"><span>Email</span>
                        <input type="email" name="email" value="<?= e($form['email']) ?>" required>
                    </label>
                    <label class="field"><span>Phone</span>
                        <input type="tel" name="phone" value="<?= e($form['phone']) ?>" required>
                    </label>
                    <label class="field"><span>Years of experience</span>
                        <input type="text" name="years_experience" value="<?= e($form['years_experience']) ?>" placeholder="e.g. 3 years">
                    </label>
                    <label class="field"><span>Availability</span>
                        <input type="text" name="availability" value="<?= e($form['availability']) ?>" placeholder="e.g. Immediately, weekdays">
                    </label>
                    <label class="field"><span>Why are you interested? (optional)</span>
                        <textarea name="message" rows="4"><?= e($form['message']) ?></textarea>
                    </label>
                    <label class="field"><span>Resume (PDF, up to 5MB)</span>
                        <input type="file" name="resume" accept="application/pdf" required>
                    </label>
                    <button class="btn-primary" type="submit">Submit application</button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l careers.php`
Expected: `No syntax errors detected in careers.php`

- [ ] **Step 3: Manual verification — no open positions yet**

Visit `http://localhost/randy/careers.php`. Since `job_positions` is empty at this point (Task 1's test row was cleaned up), confirm the page renders the hero section and "No open positions right now — please check back soon." with no PHP errors, and no application form is shown.

- [ ] **Step 4: Seed a temporary open position and verify the full submit flow**

```
C:\xampp\php\php.exe -r "require 'includes/db.php'; db()->exec(\"INSERT INTO job_positions (title, description, requirements, employment_type, pay_range, status) VALUES ('Drywall Installer', 'Install and finish drywall on residential and commercial jobs.', 'Valid driver license. 1+ years experience preferred.', 'full_time', '\$20-25/hr', 'open')\"); echo db()->lastInsertId() . PHP_EOL;"
```

Reload `http://localhost/randy/careers.php`. Confirm:
- The "Drywall Installer" position card appears with its pay range and requirements.
- Clicking "Apply for this position" jumps to the form with that position pre-selected.
- Submitting with no resume attached shows "Please attach your resume as a PDF."
- Submitting with a non-PDF file (e.g. a `.txt` renamed to `.pdf`, or any non-PDF you have on hand) shows "Resume must be a PDF file."
- Submitting with a valid small PDF and all required fields redirects to `careers.php?applied=1` and shows the "Thanks for applying!" message.
- Confirm the row landed in the DB and the file was saved:

```
C:\xampp\php\php.exe -r "require 'includes/db.php'; $a = db()->query('SELECT * FROM job_applications ORDER BY id DESC LIMIT 1')->fetch(); echo json_encode($a) . PHP_EOL; echo (is_file(__DIR__ . '/uploads/resumes/' . $a['resume_path']) ? 'file exists' : 'FILE MISSING') . PHP_EOL;"
```

Expected: JSON of the new row with `status: "new"` and `position_title_snapshot: "Drywall Installer"`, followed by `file exists`.

- [ ] **Step 5: Commit**

```bash
git add careers.php
git commit -m "feat(careers): add public careers page with application form"
```

(Leave the seeded "Drywall Installer" position and its test application in the database — they'll be visible and manageable in the admin Careers tab built in Task 7, which is a fine way to verify that tab end-to-end. Delete them manually afterward if you don't want test data lingering.)

---

## Task 5: Admin API — job positions (list, save, delete)

**Files:**
- Create: `api/careers/positions-list.php`
- Create: `api/careers/positions-save.php`
- Create: `api/careers/positions-delete.php`

- [ ] **Step 1: Create `api/careers/positions-list.php`**

```php
<?php
/** All job positions (open + closed), newest first. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$positions = db()->query('SELECT * FROM job_positions ORDER BY created_at DESC')->fetchAll();
json_out(['positions' => $positions]);
```

- [ ] **Step 2: Create `api/careers/positions-save.php`**

```php
<?php
/** Create or update a job position. Body: { id?, title, description, requirements, employmentType, payRange, status } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$title          = trim((string) ($payload['title'] ?? ''));
$description    = trim((string) ($payload['description'] ?? ''));
$requirements   = trim((string) ($payload['requirements'] ?? ''));
$employmentType = $payload['employmentType'] ?? 'full_time';
$payRange       = trim((string) ($payload['payRange'] ?? ''));
$status         = $payload['status'] ?? 'open';

if ($title === '' || $description === '') {
    json_error('Title and description are required', 422);
}
if (!in_array($employmentType, ['full_time', 'part_time', 'contract'], true)) {
    json_error('Invalid employment type', 422);
}
if (!in_array($status, ['open', 'closed'], true)) {
    json_error('Invalid status', 422);
}

$id = (int) ($payload['id'] ?? 0);
$isNew = $id <= 0;

if ($isNew) {
    db()->prepare(
        'INSERT INTO job_positions (title, description, requirements, employment_type, pay_range, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$title, $description, $requirements ?: null, $employmentType, $payRange ?: null, $status]);
    $id = (int) db()->lastInsertId();
} else {
    $st = db()->prepare('SELECT id FROM job_positions WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetch()) {
        json_error('Position not found', 404);
    }
    db()->prepare(
        'UPDATE job_positions SET title = ?, description = ?, requirements = ?, employment_type = ?, pay_range = ?, status = ? WHERE id = ?'
    )->execute([$title, $description, $requirements ?: null, $employmentType, $payRange ?: null, $status, $id]);
}

$st = db()->prepare('SELECT * FROM job_positions WHERE id = ?');
$st->execute([$id]);
json_out(['position' => $st->fetch()], $isNew ? 201 : 200);
```

- [ ] **Step 3: Create `api/careers/positions-delete.php`**

```php
<?php
/** Delete a job position. Body: { id }. Applications keep their position_title_snapshot. */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
if ($id <= 0) {
    json_error('A valid position id is required', 422);
}

db()->prepare('DELETE FROM job_positions WHERE id = ?')->execute([$id]);
json_out(['success' => true]);
```

- [ ] **Step 4: Lint all three**

Run: `C:\xampp\php\php.exe -l api/careers/positions-list.php; C:\xampp\php\php.exe -l api/careers/positions-save.php; C:\xampp\php\php.exe -l api/careers/positions-delete.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Verify the auth guard rejects unauthenticated requests**

```powershell
try { Invoke-WebRequest "http://localhost/randy/api/careers/positions-list.php" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```

Expected: `401`.

- [ ] **Step 6: Commit**

```bash
git add api/careers/positions-list.php api/careers/positions-save.php api/careers/positions-delete.php
git commit -m "feat(admin): add job positions CRUD API"
```

---

## Task 6: Admin API — job applications (list, update status, resume download)

**Files:**
- Create: `api/careers/applications-list.php`
- Create: `api/careers/applications-update.php`
- Create: `api/careers/resume-download.php`

- [ ] **Step 1: Create `api/careers/applications-list.php`**

```php
<?php
/** All job applications, newest first, with the current (or snapshot) position title. */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$applications = db()->query(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      ORDER BY a.created_at DESC'
)->fetchAll();
json_out(['applications' => $applications]);
```

- [ ] **Step 2: Create `api/careers/applications-update.php`**

```php
<?php
/** Update a job application's status. Body: { id, status } */
require_once __DIR__ . '/../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}
require_admin_api();

$payload = read_json();
$id = (int) ($payload['id'] ?? 0);
$status = $payload['status'] ?? '';
$valid = ['new', 'reviewed', 'hired', 'rejected'];

if ($id <= 0) {
    json_error('A valid application id is required', 422);
}
if (!in_array($status, $valid, true)) {
    json_error('Invalid status', 422);
}

$st = db()->prepare('SELECT id FROM job_applications WHERE id = ?');
$st->execute([$id]);
if (!$st->fetch()) {
    json_error('Application not found', 404);
}

db()->prepare('UPDATE job_applications SET status = ? WHERE id = ?')->execute([$status, $id]);

$st = db()->prepare(
    'SELECT a.*, COALESCE(p.title, a.position_title_snapshot) AS position_title
       FROM job_applications a
       LEFT JOIN job_positions p ON p.id = a.position_id
      WHERE a.id = ?'
);
$st->execute([$id]);
json_out(['application' => $st->fetch()]);
```

- [ ] **Step 3: Create `api/careers/resume-download.php`**

```php
<?php
/**
 * Stream an applicant's resume PDF (admin only). GET ?id=<application_id>.
 * Not a JSON endpoint on success — streams the raw file with a download header.
 */
require_once __DIR__ . '/../../includes/app.php';
require_admin_api();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_error('A valid application id is required', 422);
}

$st = db()->prepare('SELECT name, resume_path FROM job_applications WHERE id = ?');
$st->execute([$id]);
$app = $st->fetch();
if (!$app) {
    json_error('Application not found', 404);
}

$path = __DIR__ . '/../../uploads/resumes/' . $app['resume_path'];
if (!is_file($path)) {
    json_error('Resume file not found', 404);
}

$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $app['name']) ?: 'resume';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeName . ' - resume.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
```

- [ ] **Step 4: Lint all three**

Run: `C:\xampp\php\php.exe -l api/careers/applications-list.php; C:\xampp\php\php.exe -l api/careers/applications-update.php; C:\xampp\php\php.exe -l api/careers/resume-download.php`
Expected: `No syntax errors detected` for each.

- [ ] **Step 5: Verify the auth guard rejects unauthenticated requests**

```powershell
try { Invoke-WebRequest "http://localhost/randy/api/careers/applications-list.php" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
try { Invoke-WebRequest "http://localhost/randy/api/careers/resume-download.php?id=1" -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```

Expected: `401` for both.

- [ ] **Step 6: Commit**

```bash
git add api/careers/applications-list.php api/careers/applications-update.php api/careers/resume-download.php
git commit -m "feat(admin): add job applications list/status/resume-download API"
```

---

## Task 7: Admin "Careers" tab UI

**Files:**
- Modify: `admin/index.php`
- Modify: `assets/js/admin.js`

- [ ] **Step 1: Add the tab button and panel to `admin/index.php`**

In `admin/index.php`, the tabs block currently reads:

```html
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="reports" role="tab">Reports</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
        <button class="tab" data-tab="reviews" role="tab">Reviews</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="leads" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="reports" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
    <div data-panel="reviews" hidden></div>
```

Replace it with:

```html
    <div class="tabs" data-tabs role="tablist">
        <button class="tab is-active" data-tab="overview" role="tab">Overview</button>
        <button class="tab" data-tab="chat" role="tab">Live Chat</button>
        <button class="tab" data-tab="leads" role="tab">CRM / Leads</button>
        <button class="tab" data-tab="bookings" role="tab">Bookings</button>
        <button class="tab" data-tab="reports" role="tab">Reports</button>
        <button class="tab" data-tab="gallery" role="tab">Gallery</button>
        <button class="tab" data-tab="blog" role="tab">Blog</button>
        <button class="tab" data-tab="reviews" role="tab">Reviews</button>
        <button class="tab" data-tab="careers" role="tab">Careers</button>
    </div>

    <div data-panel="overview"></div>
    <div data-panel="chat" hidden></div>
    <div data-panel="leads" hidden></div>
    <div data-panel="bookings" hidden></div>
    <div data-panel="reports" hidden></div>
    <div data-panel="gallery" hidden></div>
    <div data-panel="blog" hidden></div>
    <div data-panel="reviews" hidden></div>
    <div data-panel="careers" hidden></div>
```

- [ ] **Step 2: Lint the PHP**

Run: `C:\xampp\php\php.exe -l admin/index.php`
Expected: `No syntax errors detected in admin/index.php`

- [ ] **Step 3: Add `initCareers()` to `assets/js/admin.js`**

In `assets/js/admin.js`, find the `initCrm` function (ends with `refresh.leads = load;` around line 640, immediately before the `const MODULES = { ... };` line). Insert this new function immediately after `initCrm` ends and before the `MODULES` line:

```js
  /* ----------  Careers  ---------- */
  function initCareers(panel) {
    const EMP_TYPES = ['full_time', 'part_time', 'contract'];
    const EMP_LABELS = { full_time: 'Full-time', part_time: 'Part-time', contract: 'Contract' };
    const APP_STATUSES = ['new', 'reviewed', 'hired', 'rejected'];
    let view = 'positions', positions = [], applications = [], appFilter = 'all', applicationsLoaded = false;

    panel.innerHTML =
      '<div class="tabs" data-cr-view style="margin-top:0">' +
      '<button class="tab is-active" data-v="positions">Positions</button>' +
      '<button class="tab" data-v="applicants">Applicants</button>' +
      '</div><div data-cr-positions></div><div data-cr-applicants hidden></div>';

    const viewTabs = panel.querySelector('[data-cr-view]');
    const positionsEl = panel.querySelector('[data-cr-positions]');
    const applicantsEl = panel.querySelector('[data-cr-applicants]');

    viewTabs.addEventListener('click', (e) => {
      const b = e.target.closest('[data-v]'); if (!b) return;
      view = b.dataset.v;
      viewTabs.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t === b));
      positionsEl.hidden = view !== 'positions';
      applicantsEl.hidden = view !== 'applicants';
      if (view === 'applicants' && !applicationsLoaded) { applicationsLoaded = true; loadApplications(); }
    });

    /* ---- Positions ---- */
    positionsEl.innerHTML =
      '<form class="app-card" data-pos-form>' +
      '<input type="hidden" name="id" value="">' +
      '<label class="field"><span>Title</span><input type="text" name="title" maxlength="150" required></label>' +
      '<label class="field"><span>Description</span><textarea name="description" rows="4" required></textarea></label>' +
      '<label class="field"><span>Requirements (optional)</span><textarea name="requirements" rows="3"></textarea></label>' +
      '<label class="field"><span>Employment type</span><select name="employmentType">' + EMP_TYPES.map((t) => '<option value="' + t + '">' + EMP_LABELS[t] + '</option>').join('') + '</select></label>' +
      '<label class="field"><span>Pay range (optional)</span><input type="text" name="payRange" maxlength="100" placeholder="e.g. $20-25/hr"></label>' +
      '<label class="field"><span>Status</span><select name="status"><option value="open">Open</option><option value="closed">Closed</option></select></label>' +
      '<div class="booking-actions"><button class="btn-primary" type="submit" data-pos-submit>Add position</button> ' +
      '<button class="btn-soft" type="button" data-pos-reset>New / clear</button></div>' +
      '</form>' +
      '<ul class="booking-list" data-pos-list></ul>';
    const posForm = positionsEl.querySelector('[data-pos-form]');
    const posList = positionsEl.querySelector('[data-pos-list]');

    function clearPosForm() {
      posForm.reset();
      posForm.querySelector('[name="id"]').value = '';
      posForm.querySelector('[data-pos-submit]').textContent = 'Add position';
    }
    posForm.querySelector('[data-pos-reset]').addEventListener('click', clearPosForm);

    posForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = posForm.querySelector('[data-pos-submit]');
      btn.disabled = true;
      try {
        await api.post('api/careers/positions-save.php', {
          id: +posForm.querySelector('[name="id"]').value || 0,
          title: posForm.title.value.trim(),
          description: posForm.description.value.trim(),
          requirements: posForm.requirements.value.trim(),
          employmentType: posForm.employmentType.value,
          payRange: posForm.payRange.value.trim(),
          status: posForm.status.value,
        });
        clearPosForm();
        await loadPositions();
        toast('Position saved');
      } catch (err) { toast(err.message, 'error'); }
      finally { btn.disabled = false; }
    });

    posList.addEventListener('click', async (e) => {
      const editBtn = e.target.closest('[data-pos-edit]');
      if (editBtn) {
        const p = positions.find((x) => String(x.id) === editBtn.dataset.posEdit);
        if (!p) return;
        posForm.querySelector('[name="id"]').value = p.id;
        posForm.title.value = p.title || '';
        posForm.description.value = p.description || '';
        posForm.requirements.value = p.requirements || '';
        posForm.employmentType.value = p.employment_type;
        posForm.payRange.value = p.pay_range || '';
        posForm.status.value = p.status;
        posForm.querySelector('[data-pos-submit]').textContent = 'Update position';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }
      const delBtn = e.target.closest('[data-pos-del]');
      if (!delBtn) return;
      if (!confirm('Delete this position? Existing applications will keep a record of the position title.')) return;
      try { await api.post('api/careers/positions-delete.php', { id: +delBtn.dataset.posDel }); await loadPositions(); toast('Position deleted'); }
      catch (err) { toast(err.message, 'error'); }
    });

    function posItem(p) {
      return '<li class="booking-item">' +
        '<div class="booking-item__head"><span class="booking-item__title">' + escapeHtml(p.title) + '</span>' +
        '<span class="badge badge--' + (p.status === 'open' ? 'confirmed' : 'cancelled') + '">' + p.status + '</span></div>' +
        '<p class="booking-item__meta">' + EMP_LABELS[p.employment_type] + (p.pay_range ? ' · ' + escapeHtml(p.pay_range) : '') + '</p>' +
        '<div class="booking-actions">' +
        '<button class="btn-soft" data-pos-edit="' + p.id + '">Edit</button> ' +
        '<button class="btn-soft" data-pos-del="' + p.id + '">Delete</button></div></li>';
    }
    async function loadPositions() {
      try {
        positions = (await api.get('api/careers/positions-list.php')).positions || [];
        posList.innerHTML = positions.length ? positions.map(posItem).join('') : '<li style="color:var(--muted)">No positions yet.</li>';
      } catch (e) { toast(e.message, 'error'); }
    }

    /* ---- Applicants ---- */
    applicantsEl.innerHTML =
      '<div class="tabs" data-app-filter style="margin-top:0">' +
      ['all'].concat(APP_STATUSES).map((s) => '<button class="tab' + (s === 'all' ? ' is-active' : '') + '" data-f="' + s + '">' + cap(s) + '</button>').join('') +
      '</div><ul class="booking-list" data-app-list></ul>';
    const appFilterEl = applicantsEl.querySelector('[data-app-filter]');
    const appList = applicantsEl.querySelector('[data-app-list]');

    appFilterEl.addEventListener('click', (e) => {
      const b = e.target.closest('[data-f]'); if (!b) return;
      appFilter = b.dataset.f;
      appFilterEl.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t === b));
      renderApplications();
    });

    function appItem(a) {
      const statusSel = '<select data-app-status data-id="' + a.id + '">' +
        APP_STATUSES.map((s) => '<option value="' + s + '"' + (s === a.status ? ' selected' : '') + '>' + cap(s) + '</option>').join('') + '</select>';
      return '<li class="booking-item">' +
        '<div class="booking-item__head"><span class="booking-item__title">' + escapeHtml(a.name) + ' — ' + escapeHtml(a.position_title || '') + '</span>' +
        '<span class="badge badge--' + a.status + '">' + a.status + '</span></div>' +
        '<p class="booking-item__meta"><a href="mailto:' + escapeHtml(a.email) + '">' + escapeHtml(a.email) + '</a> · <a href="tel:' + escapeHtml(a.phone) + '">' + escapeHtml(a.phone) + '</a></p>' +
        '<p class="booking-item__meta">Experience: ' + escapeHtml(a.years_experience || '—') + ' · Availability: ' + escapeHtml(a.availability || '—') + '</p>' +
        (a.message ? '<p class="booking-item__meta">' + escapeHtml(a.message) + '</p>' : '') +
        '<p class="booking-item__meta">Submitted: ' + fmt(a.created_at) + '</p>' +
        '<div class="booking-actions" style="align-items:center;gap:.5rem">' +
        '<a class="btn-soft" href="' + api.url('api/careers/resume-download.php?id=' + a.id) + '" target="_blank" rel="noopener">Download resume</a>' +
        '<span style="color:var(--muted)">Status:</span> ' + statusSel + '</div></li>';
    }
    function renderApplications() {
      const vis = appFilter === 'all' ? applications : applications.filter((a) => a.status === appFilter);
      appList.innerHTML = vis.length ? vis.map(appItem).join('') : '<li style="color:var(--muted)">No applicants in this status.</li>';
    }
    appList.addEventListener('change', async (e) => {
      const sel = e.target.closest('[data-app-status]'); if (!sel) return;
      const id = +sel.dataset.id;
      try {
        const d = await api.post('api/careers/applications-update.php', { id, status: sel.value });
        const i = applications.findIndex((x) => String(x.id) === String(id));
        if (i >= 0) applications[i] = d.application;
        toast('Status updated');
        if (appFilter !== 'all') renderApplications();
      } catch (err) { toast(err.message, 'error'); }
    });
    async function loadApplications() {
      try {
        applications = (await api.get('api/careers/applications-list.php')).applications || [];
        renderApplications();
      } catch (e) { toast(e.message, 'error'); }
    }

    loadPositions();
    refresh.careers = () => { loadPositions(); if (applicationsLoaded) loadApplications(); };
  }

```

- [ ] **Step 4: Register `initCareers` in the `MODULES` map**

In `assets/js/admin.js`, find this line (near the bottom of the file):

```js
  const MODULES = { overview: initOverview, chat: initChat, leads: initCrm, bookings: initBookings, reports: initReports, gallery: initGallery, blog: initBlog, reviews: initReviews };
```

Replace it with:

```js
  const MODULES = { overview: initOverview, chat: initChat, leads: initCrm, bookings: initBookings, reports: initReports, gallery: initGallery, blog: initBlog, reviews: initReviews, careers: initCareers };
```

- [ ] **Step 5: Manual browser verification**

Log in as an admin user and open `http://localhost/randy/admin/`. Confirm:
- A "Careers" tab button appears after "Reviews".
- Clicking it shows "Positions" and "Applicants" sub-tabs, with **Positions active by default**, listing the "Drywall Installer" position seeded in Task 4.
- Adding a new position via the form appears immediately in the list below.
- Clicking "Edit" on a position pre-fills the form and changes the button to "Update position"; saving updates the existing item in place (not a duplicate).
- Clicking "Delete" on a position (confirm dialog) removes it from the list.
- Switching to "Applicants" loads the test application submitted in Task 4, showing name, email/phone links, experience, availability, message, submitted date, a working "Download resume" link (opens/downloads the PDF), and a status dropdown.
- Changing the status dropdown persists after switching sub-tabs and reloading the page (re-fetches from the API).
- Filtering applicants by status (New/Reviewed/Hired/Rejected tabs) shows only matching rows.
- Open the browser console — no JS errors on load or on any interaction above.

- [ ] **Step 6: Commit**

```bash
git add admin/index.php assets/js/admin.js
git commit -m "feat(admin): add Careers tab (positions CRUD + applicant review)"
```

---

## Task 8: Status badge styling

**Files:**
- Modify: `assets/css/styles.css`

- [ ] **Step 1: Add the new badge modifiers**

In `assets/css/styles.css`, immediately after this existing line (in the "Status badges" block):

```css
.badge--closed { background: #e8eef8; color: #64748b; }
```

Add:

```css
.badge--new { background: #fef3c7; color: #92670b; }
.badge--reviewed { background: #dbeafe; color: #1e40af; }
.badge--hired { background: #dcfce7; color: #166534; }
.badge--rejected { background: #fee2e2; color: #b91c1c; }
```

- [ ] **Step 2: Verify in the browser**

Reload `http://localhost/randy/admin/` (the stylesheet has a cache-busting `?v=<filemtime>` query string from `includes/header.php`, so a normal reload is enough). Open the Careers → Applicants sub-tab. Confirm the "New" status badge is amber, and changing an application's status to Reviewed/Hired/Rejected updates its badge to blue/green/red respectively.

- [ ] **Step 3: Commit**

```bash
git add assets/css/styles.css
git commit -m "style(admin): add application status badge colors"
```

---

## Task 9: Footer link

**Files:**
- Modify: `includes/footer.php`

- [ ] **Step 1: Add the Careers link**

In `includes/footer.php`, find the "Company" footer column:

```html
                <div class="footer-col">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                        <li><a href="<?= e(url('gallery.php')) ?>">Gallery</a></li>
                        <li><a href="<?= e(url('book.php')) ?>">Get a Quote</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                    </ul>
                </div>
```

Replace it with:

```html
                <div class="footer-col">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
                        <li><a href="<?= e(url('gallery.php')) ?>">Gallery</a></li>
                        <li><a href="<?= e(url('book.php')) ?>">Get a Quote</a></li>
                        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                        <li><a href="<?= e(url('careers.php')) ?>">Careers</a></li>
                    </ul>
                </div>
```

- [ ] **Step 2: Lint**

Run: `C:\xampp\php\php.exe -l includes/footer.php`
Expected: `No syntax errors detected in includes/footer.php`

- [ ] **Step 3: Manual verification**

Load any public page (e.g. `http://localhost/randy/index.php`) and confirm a "Careers" link appears in the footer's "Company" column, and clicking it lands on `careers.php`. Confirm it does **not** appear in the header navigation.

- [ ] **Step 4: Commit**

```bash
git add includes/footer.php
git commit -m "feat(footer): add Careers link"
```

---

## Final verification

- [ ] Lint everything touched: `C:\xampp\php\php.exe -l careers.php; C:\xampp\php\php.exe -l admin/index.php; C:\xampp\php\php.exe -l includes/email.php; C:\xampp\php\php.exe -l includes/footer.php; C:\xampp\php\php.exe -l api/careers/positions-list.php; C:\xampp\php\php.exe -l api/careers/positions-save.php; C:\xampp\php\php.exe -l api/careers/positions-delete.php; C:\xampp\php\php.exe -l api/careers/applications-list.php; C:\xampp\php\php.exe -l api/careers/applications-update.php; C:\xampp\php\php.exe -l api/careers/resume-download.php`
- [ ] End-to-end as a logged-out visitor: browse `careers.php`, apply to the seeded position with a real small PDF, confirm the success page, confirm an email notification attempt is logged (check PHP error log for the `[email] job application notification...` line — it will say "not configured — skipping" unless Gmail SMTP credentials are set in `config.php`, which is expected and fine).
- [ ] End-to-end as admin: close the seeded position from the Careers → Positions tab, reload `careers.php` as a logged-out visitor, and confirm it no longer appears (and the "no open positions" message shows if it was the only one).
- [ ] Confirm all `api/careers/*` endpoints reject unauthenticated/non-admin requests (401/403) — already spot-checked per-task, but re-verify `positions-save.php` and `positions-delete.php` and `applications-update.php` specifically since they weren't hit by the earlier GET-only 401 checks:

```powershell
try { Invoke-WebRequest "http://localhost/randy/api/careers/positions-save.php" -Method POST -Body '{}' -ContentType 'application/json' -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
try { Invoke-WebRequest "http://localhost/randy/api/careers/positions-delete.php" -Method POST -Body '{}' -ContentType 'application/json' -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
try { Invoke-WebRequest "http://localhost/randy/api/careers/applications-update.php" -Method POST -Body '{}' -ContentType 'application/json' -UseBasicParsing } catch { $_.Exception.Response.StatusCode.value__ }
```

Expected: `401` for all three.
- [ ] Confirm `uploads/resumes/` resumes are not directly downloadable by URL (Task 2's check) but are downloadable via the admin Careers → Applicants "Download resume" link while logged in as admin.
- [ ] Delete any leftover test data (the seeded "Drywall Installer" position and its test application, and the throwaway `.htaccess` test file if not already removed) once verification is complete — or leave them if the owner wants a real starter listing.

---

## Spec coverage check

- Public `careers.php` listing open positions with an "Apply" action → Task 4 ✓
- Application form fields (name, email, phone, position, years experience, availability, message, PDF resume) → Task 4 ✓
- Admin-managed positions (title, description, requirements, employment type, pay range, open/closed) → Task 1 (schema) + Task 5 (API) + Task 7 (UI) ✓
- Admin applicant review: filter, view, download resume, status pipeline New → Reviewed → Hired/Rejected → Task 1 (schema) + Task 6 (API) + Task 7 (UI) + Task 8 (badges) ✓
- Best-effort email notification on new application → Task 3 (function) + Task 4 (call site) ✓
- Footer-only link, no header nav change → Task 9 ✓
- Resume privacy (`.htaccess` deny + gated download endpoint) → Task 2 (deny) + Task 6 (gated endpoint) ✓
- `position_title_snapshot` preserves history when a position is deleted → Task 1 (`ON DELETE SET NULL` + snapshot column) + Task 4 (populated on insert) + Task 5/6 (`COALESCE` fallback in queries) ✓
- No SMS, no applicant accounts, PDF-only resumes, single "Careers" tab (not two) → honored by omission across all tasks (no code added for any of these) ✓
