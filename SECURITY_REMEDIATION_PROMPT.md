# ITARA — Security Remediation & Prevention Prompt

Companion to `SECURITY_REVIEW_PROMPT.md` (that file *finds* weaknesses; this one **fixes** them and stops them coming back). Review/fix only — no exploit code, no payloads.

## COPY FROM HERE

> You are a senior PHP security engineer. We completed a defensive white-box review
> of ITARA (my own app). Your job: implement the fixes for the findings below in this
> exact codebase, then install guardrails so they cannot regress. Do NOT write exploit
> payloads or attack tooling — deliver patches + verification steps.
>
> STACK: PHP 8+ custom MVC (front controller `index.php`, `App\Core\Router`), MySQL via
> PDO (`config/database.php`, ATTR_EMULATE_PREPARES=false), session auth
> (`App\Core\Session`, cookie `itassets_session`), bcrypt 12, CSRF via
> `App\Middleware\CsrfMiddleware` + `Security::csrfField()`, RBAC via
> `Controller::requireRole()` / `App\Middleware\RoleMiddleware`, uploads via
> `App\Helpers\File` + `Security::validateFileUpload()`, views `views/**`,
> JS `public/assets/js/**`, XAMPP on Windows (`c:\xampp\htdocs\Itara`).
> ROLES: admin (full) / user (own tickets, admin-only messaging) / viewer (read-only).
>
> WORKING RULES (non-negotiable)
> 1. One finding per patch. Smallest diff that closes it. No refactors, no reformatting,
>    no new dependencies — PHP 8 core only.
> 2. Reuse existing classes/helpers (`Security`, `Session`, `Database`, `Response`,
>    `Controller`, `File`). Never create a second pattern for the same job.
> 3. Preserve behaviour for legitimate users: admin/user/viewer flows and the JS callers
>    in `public/assets/js/**`. If a JS caller must change, show the change.
> 4. Never print secrets. Never enable APP_DEBUG. Never disable CSRF/RBAC "to test".
> 5. Every patch ships with: exact file:line, before/after code, any SQL migration
>    (idempotent — `CREATE TABLE IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS`), a manual
>    verification step, and the regression test that proves it.
> 6. If a finding is already fixed or not exploitable in the current code, say so with
>    evidence and skip it — do not invent work.
>
> THE BACKLOG (work top-down unless I say otherwise)
> F1  HIGH  CWE-306  Unauthenticated web-reachable scripts mutate the schema, send mail,
>    leak internals. Files: `migrate_asset_size.php`, `migrate_section.php`,
>    `migrate_assigned_name.php`, `migrate_topology_upgrades.php`, `migrate_vm_type.php`,
>    `test_mail_queue.php`, `test_routes.php`, `smoke_test.php`, `test.php`.
>    FIX: CLI-only guard copied from `mail_queue_worker.php:16-19`, or move them to
>    `scripts/cli/` + `scripts/.htaccess` with `Require all denied`.
> F2  HIGH  CWE-1392 All three roles share one "admin123" password seeded at cost 10
>    (`database/seed.sql:5-9`), plaintext creds in `test_routes.php:26` and `README.md`.
>    FIX: remove literals; distinct per-role hashes at cost 12 via `Security::hashPassword`;
>    seed only when `users` is empty; force password change on first login (session flag
>    + profile view).
> F3  HIGH  CWE-250  DB connects as `root` with an empty password
>    (`config/database.php:14-15`, `migrate_section.php:9`). FIX: least-privilege
>    `itara_app` user (SELECT/INSERT/UPDATE/DELETE only — no FILE/ALTER/DROP/GRANT),
>    env-only secrets, fail fast in production when DB_PASS is unset.
> F4  HIGH  CWE-434  Uploads: extension-only validation (`Security.php:177-193`), `svg`
>    allowed (`config/app.php:42`), no `.htaccess` anywhere under `public/`.
>    FIX: `getimagesize()` + `finfo` MIME check BEFORE `move_uploaded_file`
>    (`File.php:53`); drop/sanitise svg; add `public/uploads/.htaccess` denying
>    php|phtml|svg|html + `php_flag engine off` + `Options -Indexes`.
> F5  HIGH  CWE-307  Login rate limit stored in the visitor's own session
>    (`Security.php:147-163`, key at `AuthService.php:32`) => reset by dropping the
>    cookie; `Request::ip()` (`Request.php:162-174`) trusts `X-Forwarded-For` =>
>    forgeable audit IP. FIX: server-side `login_attempts` table keyed ip+username;
>    trust proxy headers only from configured proxies; record real peer IP in `audit_log`.
> F6  MED   CWE-89   Identifier interpolation in `Model::findBy/all/paginate/count/search`
>    (`Model.php:52,66,83,96,176,194`). FIX: `safeIdentifier()`/`safeDirection()`
>    allowlist guards that throw on invalid input.
> F7  MED   CWE-352  State-changing GETs outside CSRF coverage (`CsrfMiddleware.php:16`):
>    `GET /logout`, `/tickets/backup/run`, `/tickets/backup/download`,
>    `/mail-queue/process`, `/assets/export/csv` (`index.php:56,190,192,195,73`), plus
>    `backup.php`. FIX: POST + `Security::csrfField()` for actions; `X-CSRF-Token` header
>    check for GETs that must stay GET (downloads/exports).
> F8  MED   CWE-601  Referer guard is a case-insensitive substring match
>    (`Controller.php:56-65`, `CsrfMiddleware.php:28-33`); `Response::redirect`
>    (`Response.php:32-40`) passes absolute URLs through. FIX: shared helper using
>    `parse_url()` host compare + `APP_BASE_PATH` path-prefix check.

> F9  MED   CWE-614  Session `secure` config is never applied (`config/app.php:21` vs
>    `Session.php:43-52`); remember-me cookie hardcoded insecure
>    (`AuthService.php:107-113`). FIX: derive `$secure` from config OR HTTPS detection and
>    apply it to `session.cookie_secure` and both cookies.
> F10 MED   CWE-214  `backup.php:54,58-69` puts the DB password on the mysqldump command
>    line and mixes `escapeshellarg()` with its own quoting. FIX: use the existing PHP
>    fallback (`backup.php:74-118`) or `--defaults-extra-file` with a protected temp file;
>    never pass the password in argv.
> F11 LOW   CWE-863  Messaging matrix is inverted: `user` is limited to admins but
>    `viewer` is unrestricted, and `MessageController.php` has no `requireRole()` call
>    (`:71,:150,:182`). FIX: one `mayMessage(array $actor, array $peer): bool` matrix
>    per role, used by `conversation()`, `send()` and `poll()`.
> F12 LOW   CWE-538  Web-root artifacts served publicly (`presentation.docx`,
>    `presentation_backup_*.docx`, `_tmp_pres.zip`, `Future plan.txt`, the `-w` file,
>    empty `test.php`) and legacy Apache 2.2 `Order allow,deny` in `.htaccess:25-33`.
>    FIX: move artifacts outside the docroot / delete dead files; extend the `FilesMatch`
>    to `txt|docx|xlsx|zip|json|ini|yml` and convert to `Require all denied`.
> F13 LOW   CWE-693  No CSP/HSTS/Permissions-Policy; obsolete `X-XSS-Protection`
>    (`.htaccess:12-19`). FIX: headers block; ship CSP report-only first because
>    `public/assets/js/topology.js:1241` builds an inline `onclick` handler.
> F14 LOW   CWE-1236 CSV formula injection in `ExportService.php:34-58`. FIX: neutralise
>    leading `= + - @` tab/CR with a leading apostrophe before `fputcsv`.
> F15 LOW   CWE-352  CSRF token is session-wide and not rotated at privilege change
>    (`Security.php:14-24`, `Session.php:136-142`). FIX: remove `_csrf_token` inside
>    `setUser()` and `destroy()` so the next `csrfField()` mints a fresh value.
>
> DELIVERY FORMAT per finding
> [F#] STATUS: FIXED | ALREADY-SECURE | NEEDS-DECISION
> Files: path:line
> Patch: before/after snippet (```php / ```apache / ```sql)
> Migration: only if schema changes — idempotent + rollback note
> Verification: exact manual step (what to click/run, what must change) — no payloads
> Regression test: file + assertion (e.g. `tests/security_checks.php` asserts 403)
> Risk if skipped: one concrete line
>
> ORDER OF WORK
> P1 today: F1, F4 (.htaccess half), F12, F13, F2 (literal purge)
> P2 this week: F5, F9, F8, F10, F4 (content validation), F7
> P3 this month: F6, F11, F14, F15, F3 (migrate to least-privilege user), CSP enforced
> P4 ongoing: regression suite + the prevention rules below
>
> PREVENTION — bake these in, and tell me where each one is enforced
> 1. New route? Declare method, auth, role, CSRF. State-changing work is POST-only.
>    Every route gets a role check or an explicit "any authenticated user" comment
>    (ApiController and MessageController are the current exceptions).
> 2. New query? Bound values only; identifiers must pass the Model allowlist. Never
>    interpolate a column name or ORDER BY from request data.
> 3. New output? `Security::escape()`/`htmlspecialchars` for HTML, `Response::json()`
>    for JSON, and never `innerHTML` in JS without an `escHtml()` equivalent.
> 4. New upload or file write? Content-sniff + allowlist, store outside the docroot or
>    behind the uploads `.htaccess`. Never trust the client-supplied filename.
> 5. New script under the web root? It MUST start with the CLI guard or be denied by
>    `.htaccess`. No exceptions for "temporary" scripts.
> 6. New secret? Env var only — never in `config/*.php`, `seed.sql`, README or tests.
> 7. Errors: APP_DEBUG stays env-driven and false by default; never echo exception or
>    DB details to the browser.
> 8. Rate-limit and audit state lives server-side (DB), keyed on the real peer IP.
>
> ALSO DELIVER (prevention layer, after the patches)
> a) `docs/security-checklist.md` — the 8 rules above as a PR checklist, each with one
>    line on how a reviewer verifies it.
> b) `tests/security_checks.php` — CLI-guarded (same guard as `mail_queue_worker.php`)
>    assertions: migrate_*/test_* return 403 over HTTP; viewer gets 403 on `/users`;
>    POST without a token is refused; a mislabelled upload is rejected; the session cookie
>    carries `Secure` under HTTPS; `/public/uploads/**` serves no php/phtml/svg. Print
>    PASS/FAIL per check and `exit(1)` on failure — same shape as `smoke_test.php`.
> c) Hardened `.htaccess` + security-headers block (Apache 2.4 syntax) plus a short
>    production list: HTTPS + Secure cookies, APP_DEBUG off, least-privilege DB user,
>    scripts/backups out of the docroot, `mail_queue_worker.php` and the backup scheduled
>    via Task Scheduler/cron.
> d) A 5-line "how we prevent recurrence" summary for the README.
>
> DEFINITION OF DONE: every finding is FIXED or ALREADY-SECURE with evidence; the
> regression suite passes; no legitimate admin/user/viewer flow is broken; no secret is
> printed anywhere; and the prevention checklist exists in the repo.
>
> Ask me for missing context (table schemas, JS call sites, hosting details) before
> assuming. Start with P1/F1 and F4's `.htaccess` half, show the patch, then continue
> in order.

## END COPY

## How to use

1. Paste the block above into your assistant with this repo open (it already knows the paths).
2. Work **one finding at a time**; after each patch, run that finding's verification step.
3. Add the regression assertion to `tests/security_checks.php` as you go, so the suite
   grows with the fixes and later changes cannot silently reopen a finding.
4. Re-run `SECURITY_REVIEW_PROMPT.md` quarterly — or after any change to auth, uploads,
   routing, or SQL — and diff the new findings against this backlog.

