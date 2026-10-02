# ITARA — Unified Audit Log + Asset ID Generation Prompt

Goal: (A) make the audit trail record **everything** (not only asset changes) and show it on one page, and (B) replace asset-tag generation with the standard format `O-YYYY-DEPT-NNNN` plus a backfill for legacy assets that have no real tag.

## CURRENT STATE (verified against code — do not re-guess)

Two parallel history stores exist and only ONE is shown on the Audit Log page:

1. `asset_history` table (`database/schema.sql:91`) — requires a valid `asset_id` FK, ENUM action
   (`created,updated,deleted,assigned,transferred,maintenance,retired`). Written by
   `App\Models\AssetHistory::log()` via `App\Services\AuditService` (`logCreated/logUpdated/logDeleted/...`)
   — used only by `AssetController` (create/update/retire/delete) and shown on the asset detail page.
2. `audit_log` table (`database/schema.sql:112`) — columns `user_id, action, description, ip_address, created_at`.
   Written by `AuthService::logAction()` (login/logout/profile_update/password_change) and
   `AuditService::logAction()` (tickets, licenses, disposals, backups, instructions) — these entries are
   **NOT shown on the `/history` page**.
3. `/history` page = `HistoryController@index` (`src/Controllers/HistoryController.php:16`) —
   selects **only** from `asset_history`. That is why "audit log only records assets".
4. NO audit calls at all in: `UserController`, `DepartmentController`, `SettingsController`,
   `TopologyController`, `MessageController`, `MailQueueController`, `StartupController`,
   `BackupController`/`backup.php`, and failed-login attempts (`AuthService::attempt()`).
5. `AuditService::getUserId()` returns `0` when no session user exists (`AuditService.php:137-141`),
   which violates the `audit_log.user_id` FK (expects NULL or an existing id) — inserts throw and the
   entry is silently lost. `AssetHistory::log()` has the same problem via the `asset_history` FK.
6. Asset tag generator today: `Asset::nextAssetTag(string $type)` (`src/Models/Asset.php:228-256`) —
   produces `PC-001`, `LAP-004`, … Wired into `AssetController::create()` (`$nextTag`, line 78) and
   prefilled in `views/assets/create.php:39`. `assets.asset_tag` is `VARCHAR(50) NOT NULL UNIQUE`
   (`schema.sql:48`). Department codes already exist: `departments.code` (IT, HR, FIN, ENG, MKT, OPS).

## COPY FROM HERE (Part A — unify the audit log)

> You are a senior PHP engineer working in my own app ITARA. Implement a UNIFIED audit trail.
>
> STACK: PHP 8 custom MVC (`index.php` front controller, `App\Core\Router`), MySQL/PDO via
> `App\Core\Database` (has `insert/fetch/fetchAll`), XAMPP Windows (`c:\xampp\htdocs\Itara`),
> views in `views/**`, RBAC via `Controller::requireRole()`. Reuse existing classes only — no new
> dependencies, no framework. Keep the smallest diff that satisfies each step.
>
> DESIGN DECISION (already made — implement exactly this):
> `audit_log` becomes the SINGLE source of truth. `asset_history` stays for per-asset detail pages
> but stops being the audit UI's data source.
>
> STEP 1 — Migration `database/migrations/021_unify_audit_log.sql` (idempotent):
> ALTER `audit_log` ADD columns:
>   `entity_type` VARCHAR(30) DEFAULT NULL COMMENT 'asset|ticket|license|user|department|setting|topology|message|disposal|backup|auth',
>   `entity_id`   INT UNSIGNED DEFAULT NULL,
>   `entity_ref`  VARCHAR(80) DEFAULT NULL COMMENT 'human ref: asset_tag, ticket_number, License #4',
>   `field_changed` VARCHAR(50) DEFAULT NULL,
>   `old_value` TEXT DEFAULT NULL,
>   `new_value` TEXT DEFAULT NULL;
> ADD INDEX `idx_audit_entity` (`entity_type`,`entity_id`).
> Then backfill existing rows:
> INSERT INTO audit_log (user_id, action, description, ip_address, created_at, entity_type,
>   entity_id, entity_ref, field_changed, old_value, new_value)
> SELECT h.user_id, h.action, CONCAT('Asset ', COALESCE(a.asset_tag, CONCAT('#', h.asset_id))),
>   h.ip_address, h.created_at, 'asset', h.asset_id, a.asset_tag, h.field_changed,
>   h.old_value, h.new_value
> FROM asset_history h LEFT JOIN assets a ON a.id = h.asset_id
> WHERE NOT EXISTS (SELECT 1 FROM audit_log x WHERE x.entity_type='asset' AND x.entity_id=h.asset_id
>   AND x.action=h.action AND x.created_at=h.created_at AND x.user_id <=> h.user_id);
>
> STEP 2 — Extend `App\Services\AuditService` with ONE canonical method:
> `public function log(string $action, string $entityType, ?int $entityId = null, ?string $entityRef = null, ?string $field = null, mixed $old = null, mixed $new = null, ?string $description = null): void`
> - Writes to `audit_log` only. Cast values to string, NULL-safe.
> - `getUserId()` must return `?int` and pass NULL (never 0) when no session user — fixes the
>   silent FK failure described in CURRENT STATE #5. Apply the same fix in `AssetHistory::log()`.
> - Keep all existing `logCreated/logUpdated/logDeleted/logAction/...` methods as thin wrappers that
>   write to BOTH stores where a valid asset_id exists (asset detail pages must keep working).
>   Existing call sites in Asset/Disposal/License/Ticket/Instructions/IncrementalBackup controllers
>   must NOT need edits.
> - Make `log()` itself fail-soft (catch + `error_log`) so a logging error never breaks a user action.
>
> STEP 3 — Instrument the uncovered controllers (log only state-changing, role-guarded actions):
> - `UserController`: `user_created`, `user_updated`, `user_deleted`, `user_role_changed`,
>   `user_password_reset`, `user_deactivated` (entity_type='user', entity_ref=username).
> - `DepartmentController`: `department_created/updated/deleted` (entity_type='department', ref=code).
> - `SettingsController`: `setting_updated` — one entry per changed key with old/new value.
> - `TopologyController`: `topology_saved`, `link_created`, `link_deleted`
>   (entity_type='topology'; keep bulk node moves to ONE entry per save, not per node).
> - `MessageController`: `message_sent`, `message_deleted` (entity_ref = conversation id only —
>   never log full message bodies, privacy).
> - `MailQueueController` + `mail_queue_worker.php`: `mail_queued`, `mail_sent`, `mail_failed`.
> - `AuthController`/`AuthService`: `login_failed` (entity_ref=username, description
>   'invalid credentials' — never log the password), `account_locked` when the rate limiter fires.
>   Use one IP source (`App\Core\Request::ip()` or REMOTE_ADDR — pick the existing convention and
>   use it everywhere; do not read `$_SERVER['REMOTE_ADDR']` inline in new code).
> Every call = one line using `AuditService::log()`.
>
> STEP 4 — Rewrite `HistoryController@index` to read the unified `audit_log`:
> - JOIN users for name/avatar; columns: time, user, action, entity (type + ref with link to the
>   record), field / old→new (only when present), IP.
> - Filters via GET: `entity_type`, `action`, `user_id`, `from`, `to` — every value bound as a
>   prepared-statement parameter (no interpolation of user input into SQL; note the existing
>   ORDER BY interpolation issue called out in SECURITY_REMEDIATION_PROMPT.md F6 — whitelist it).
> - Pagination like today (50/page + COUNT). Update `views/history/index.php` keeping the current
>   table/styling conventions of `views/layouts/main.php`.
> - Access stays `requireRole('admin','viewer')`. Admins only get `?export=csv`; escape cells
>   starting with `= + - @` by prefixing `'` (CSV formula injection).
>
> STEP 5 — Config: honour `config/app.php` `'audit' => ['enabled' => ...]` (documented already in
> `docs/deployment.md:133`). When disabled, `AuditService::log()` and the old `logAction()` both
> no-op.
>
> VERIFY before finishing:
> 1) `php -l` on every changed file.
> 2) Run the migration twice — second run must change nothing.
> 3) Manual: login, create+edit+delete an asset, create/edit a user, change a setting, send a
>    message, fail one login — open `/history` and confirm ALL events appear with correct actor,
>    entity link, old/new values.
> 4) Asset detail page still shows its per-asset history (`/history/asset/{id}`).
> 5) Login as viewer → export option absent. Login as user → /history blocked per current rules.
> Deliver as one patch-set with the migration script first.

## COPY FROM HERE (Part B — Asset ID generation `O-YYYY-DEPT-NNNN`)

> Replace the asset tag generator with the org standard format:
>
> **Format:** `{ORG}-{YYYY}-{DEPT}-{NNNN}`  e.g. `O-2026-IT-4821`
> - `{ORG}` = org/site letter, default `O`. Store it in the existing `settings` table as key
>   `asset.tag.org` (editable on the Settings page) so no code change is needed per site.
> - `{YYYY}` = acquisition year = year of `purchase_date`; fall back to the current year when
>   purchase_date is empty (it often is for the untagged PCs).
> - `{DEPT}` = `departments.code` of the selected department (`AssetController::create()` already
>   loads `Department::getOptions()`). When department is empty use `GEN` so the format never breaks.
> - `{NNNN}` = 4 random digits `1000–9999` via `random_int()`.
>
> WHERE TO PUT IT (do not invent a second generator):
> 1. `src/Models/Asset.php` — keep `nextAssetTag(string $type)` as a deprecated wrapper and ADD:
>    `public static function generateAssetTag(?int $departmentId = null, ?string $year = null): string`
>    - Resolves ORG from the `Setting` model (`asset.tag.org`, default 'O') and DEPT code via
>      `Department::find($departmentId)['code']`.
>    - Uniqueness loop: up to 30 attempts of
>      `sprintf('%s-%s-%s-%04d', $org, $y, $code, random_int(1000, 9999))`, each checked with
>      `SELECT 1 FROM assets WHERE asset_tag = ?`. After 30 collisions fall back to highest
>      existing sequence `+1` for that `ORG-YYYY-DEPT-` prefix, so generation can never fail.
>    - Final safety net: `AssetController::store()` catches the `asset_tag` UNIQUE constraint
>      violation and retries generation once (race between two admins on the create form).
> 2. `src/Controllers/AssetController.php` — in `create()` (line ~78) replace
>    `Asset::nextAssetTag('pc')` with `Asset::generateAssetTag()` (defaults; JS refreshes it).
>    ADD `public function nextTag(): void` returning JSON `{"success":true,"tag":"O-2026-IT-4821"}`
>    for `GET /assets/next-tag?department_id=&year=` (admin-only via `requireRole('admin')`;
>    validate year against `^(19|20)\d{2}$`, department_id as int). Register the route in
>    `index.php` next to the other `/assets` routes (line ~111).
> 3. `views/assets/create.php` — the prefilled `asset_tag` input (line 39):
>    - keep it editable (admins must still key an existing vendor tag manually),
>    - add a small `↻ Generate` button beside it,
>    - vanilla JS (match the style of `public/assets/js/**`): on change of `department_id` or
>      `purchase_date`, fetch `/assets/next-tag?department_id=…&year=…` and refill the input.
> 4. Legacy backfill for the PCs that have no tag — `scripts/cli/generate_missing_asset_tags.php`
>    (CLI-only guard copied from `mail_queue_worker.php:16-19`):
>    - Targets assets whose `asset_tag` is empty/placeholder (`^[A-Z]{2,3}-\d{3}$`, e.g. PC-001)
>      or obvious stand-ins (hostname-only values).
>    - For each: `Asset::generateAssetTag(department_id, YEAR(purchase_date))`, then write an
>      `asset_history` row AND a unified audit_log entry (`action='tag_generated'`,
>      old→new tag) so the change is itself audited.
>    - Support `--dry-run` printing the planned tag table first. Never touch rows already matching
>      `^O-\d{4}-[A-Z]{2,4}-\d{4}$`.
>    - Run once: `C:\xampp\php\php.exe scripts\cli\generate_missing_asset_tags.php --dry-run`
>      review, then without `--dry-run`.
>
> VERIFY:
> - Create assets with/without department and purchase_date → tags like `O-2026-IT-1234` /
>   `O-2026-GEN-5678`.
> - Hit `/assets/next-tag` twice fast → two different tags, and saving both succeeds.
> - Backfill dry-run lists exactly the untagged PCs; after the real run, `/history` shows one
>   `tag_generated` audit entry per asset.

## NOTES / ASSUMPTIONS
- "O" is treated as a configurable org-site **letter**, not the digit zero. You already have rows
  like `0-90-IT-1872`; if those were meant to be this format, tell the agent to additionally
  normalize `^\d-\d{2}-[A-Z]+-\d{4}$` rows in the backfill script.
- 4 random digits = 9,000 combos per year+department — plenty per org per year, and the
  deterministic `+1` fallback guarantees uniqueness when it isn't.
- Do NOT drop `asset_history` — asset detail pages, `ApiController` and `DashboardController`
  recent-activity widgets depend on it.
- Part A and Part B are independent; run Part A first (Part B's backfill writes entries through the
  new unified `AuditService::log()`).
