# ITARA — Defensive Security Review Prompt

Paste this into ChatGPT / Gemini / Claude to get a hardening review of this app.
Review-only: it asks for weaknesses and fixes, not exploit code.

## COPY FROM HERE

> You are a senior application-security engineer doing a DEFENSIVE white-box
> security review of my own PHP application (ITARA). Goal: identify weaknesses
> in this exact codebase and recommend concrete fixes. Analyze exploitability
> conceptually — do NOT write exploit payloads, PoC attack commands, or tooling.
>
> STACK: PHP 8+ custom MVC (front controller `index.php` + `App\Core\Router`),
> MySQL via PDO, session auth, vanilla JS, XAMPP/Apache, Windows.
> ROLES: admin (full), user (own tickets), viewer (read-only).
> CONTROLS ALREADY IN PLACE: PDO prepared statements (emulation off), CSRF
> middleware on POST/PUT/DELETE, bcrypt cost 12, session regeneration on login
> and hourly, remember-me selector/validator (SHA-256 stored), `requireRole()`
> RBAC, `.htaccess` deny for /config /src /database /storage.
> REVIEW SCOPE (files): index.php, .htaccess, config/*.php, src/Core/*.php,
> src/Helpers/{Security,File}.php, src/Middleware/*.php,
> src/Controllers/*.php, src/Services/*.php, src/Models/*.php,
> public/assets/js/*.js, backup.php, migrate_*.php, test_*.php,
> database/seed.sql, views/**/*.php.
>
> CHECK, AT MINIMUM:
> 1. Broken access control: every route in index.php vs. role checks in the
>    controller constructor and per-action `requireRole()`; ownership checks on
>    tickets/messages/conversations; admin-only routes.
> 2. Authentication: login flow in AuthService, rate limiting in
>    Security::checkRateLimit (where is attempt state stored?), remember-me
>    token lifecycle, default credentials in seed.sql/test scripts.
> 3. Injection: any SQL built by string concatenation or interpolation
>    (Model::findBy, Model::all ORDER BY, Model::paginate $where, custom
>    controller queries); shell_exec/exec in backup.php; CSV formula injection
>    in ExportService exports.
> 4. XSS: views/**/*.php output escaping, DOM sinks in
>    public/assets/js/*.js (innerHTML), uploaded SVG files.
> 5. CSRF: state-changing GET routes (logout, mail-queue, backup run/download,
>    exports) not covered by CsrfMiddleware; redirect logic in
>    CsrfMiddleware and Controller::redirectBack (referer substring checks).
> 6. File upload: Security::validateFileUpload (extension-only?), MIME/content
>    validation, public/uploads web exposure, missing .htaccess there.
> 7. Sensitive data exposure: scripts reachable over HTTP without auth
>    (migrate_*.php, test_*.php), credentials in seed.sql/README/scripts,
>    backup files under storage/, cookie flags in config/app.php, security
>    headers in .htaccess.
> 8. Logging/audit integrity: Request::ip() header trust, audit_log fields.
> 9. Latent risks: Model identifier interpolation (findBy column, orderBy),
>    mass-assignment via $fillable, session fixation, error handling with
>    APP_DEBUG.
>
> OUTPUT FORMAT per finding:
> [SEVERITY] [CWE-XXX] Title
> Location: file:line
> Why it's weak (2-3 sentences, reference the actual code)
> Fix: before/after snippet using this codebase's own classes/helpers
> Verification: how I confirm the fix works (e.g. "viewer now gets 403",
>   "token required on this route") — verification steps only, no payloads
>
> Then: (a) findings table sorted by severity, (b) top-5 quick fixes under 30
> minutes each, (c) a prioritized remediation roadmap, (d) a hardened .htaccess
> + security-headers snippet for XAMPP/Apache.
> If a control is implemented correctly, list it under SECURE: with one line
> explaining why — so I don't "fix" what isn't broken.
> Ask me for missing context before assuming. When I paste a file, audit it
> line by line — no generic OWASP boilerplate.
>
> Start with: the 5 highest-severity findings you can identify from the files
> I give you, then the roadmap.

## END COPY

## Next step

Once you have the findings, hand them to `SECURITY_REMEDIATION_PROMPT.md`, which turns each
finding into a patch + verification + regression test and adds a prevention checklist.
