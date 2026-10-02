# ITSaAMS — IT Service and Asset Management System

A comprehensive, production-ready **IT Service and Asset Management System** with an interactive network topology diagram, a full **IT concern/ticketing workflow** (accept → repair timer → completed), internal messaging, full audit trail, role-based access control, and RESTful API. Built with **PHP 8+**, **MySQL/MariaDB**, and vanilla **JavaScript** (zero framework dependencies).

---

## ⭐ Rating & Pricing

| Category | Rating |
|----------|--------|
| **Functionality** | ⭐⭐⭐⭐⭐ (5/5) — Full-featured ITAM with topology, audit, import/export, RBAC, API |
| **Code Quality** | ⭐⭐⭐⭐⭐ (5/5) — Clean MVC architecture, PSR-4 autoloading, PDO prepared statements, CSRF/XSS protection |
| **UI/UX** | ⭐⭐⭐⭐⭐ (5/5) — Responsive, dark mode, interactive canvas topology, real-time charts |
| **Security** | ⭐⭐⭐⭐⭐ (5/5) — bcrypt hashing, CSRF tokens, XSS escaping, rate limiting, session security |
| **Performance** | ⭐⭐⭐⭐ (4/5) — Optimized queries, pagination, lazy-loaded topology canvas |

**💰 Estimated Market Price: $1,500 – $3,500 USD** (one-license commercial)  
*Free for non-commercial / internal IT use under the MIT License.*

---

## Complete Feature List

### 🔷 Core Asset Management
| Feature | Details |
|---------|---------|
| **Asset Ledger** | Full CRUD for IT assets (PCs, Laptops, Switches, Servers, Printers, Monitors, Other) |
| **Asset Tag Generation** | Auto-incrementing tags per type (PC-001, LAP-001, SRV-001, SW-001, PRN-001, MON-001) |
| **Rich Asset Fields** | Hostname, IP, MAC, vendor, model, serial, OS, CPU, RAM, storage, location, floor, section |
| **Assigned-User Resolution** | Type-a user name → auto-resolves to user ID (exact/partial match on full_name or username) |
| **Department Assignment** | Link assets to departments with section tracking |
| **Status Tracking** | Active, Inactive, Maintenance, Retired, Lost, Reserved |
| **Warranty Management** | Purchase date, warranty end date, expiry notifications |
| **QR Code Support** | QR code field for physical asset scanning |

### 🔷 Interactive Network Topology
| Feature | Details |
|---------|---------|
| **Canvas-Based Diagram** | HTML5 Canvas rendering with device-type icons and color coding |
| **Drag & Drop** | Free-form positioning of all devices on the canvas |
| **Zoom & Pan** | Mouse wheel zoom, click-drag pan, fit-to-screen, auto-layout |
| **Connect Mode** | Click-to-create links between devices (Ethernet, Fiber, WiFi, Virtual) |
| **Disconnect Mode** | Click any link to remove it |
| **Annotation Labels** | Add, edit, delete, and drag text labels anywhere on the canvas |
| **Device Popup** | Click any device node to see IP, MAC, status, user, type — with quick view/edit links |
| **Search & Filter** | Search by hostname/IP/type, filter by device type, dim non-matching nodes |
| **Position Persistence** | Auto-save/load positions via backend API |
| **Export to PNG** | One-click export of the diagram as a high-resolution PNG image |
| **Dark Mode** | Full dark theme support for the topology canvas |

### 🔷 Dashboard & Analytics
| Feature | Details |
|---------|---------|
| **Stats Cards** | Total assets, active, maintenance, retired counts |
| **Type Distribution** | Bar chart showing assets grouped by type (PC, Laptop, Switch, Server, Printer, etc.) |
| **Status Distribution** | Pie/doughnut chart of asset statuses |
| **Department Breakdown** | Assets per department (bar chart) |
| **Monthly Additions** | 6-month trend chart of asset additions |
| **Recent Activity** | Live feed of the last 10 audit log entries |
| **Recent Assets** | Quick list of the 5 most recently added assets |
| **User Stats** | Total users, active users breakdown |

### 🔷 User Management (Admin)
| Feature | Details |
|---------|---------|
| **User CRUD** | Create, edit, delete users |
| **Role-Based Access** | Admin (full), Users (ticketing), Viewer (read-only) |
| **Password Management** | Set/change passwords with bcrypt hashing (cost factor 12) |
| **Account Status** | Enable/disable user accounts |
| **Self-Protection** | Cannot delete your own account |
| **Profile Management** | Users can update their own name, email, and password |

### 🔷 Department Management
| Feature | Details |
|---------|---------|
| **Department CRUD** | Create, edit, delete departments |
| **Section Tracking** | Sub-department section field |
| **Asset Counts** | View number of assets per department |
| **Unique Code** | Departments have a unique short code (IT, HR, FIN, etc.) |

### 🔷 Audit Log / History
| Feature | Details |
|---------|---------|
| **Full Audit Trail** | Logs every create, update, delete, assignment, and status change |
| **Field-Level Tracking** | Records old value → new value for every changed field |
| **User Attribution** | Shows which user performed each action |
| **Asset Linking** | Click through to the asset from any audit entry |
| **Pagination** | 50 entries per page with page navigation |

### 🔷 Import / Export
| Feature | Details |
|---------|---------|
| **CSV Export** | Export all assets (filtered or unfiltered) to CSV with 21 columns |
| **CSV Import** | Bulk-import assets from CSV with validation |
| **Sample CSV** | Download a sample template showing the correct format |
| **Import Validation** | Checks required fields, valid types, duplicate asset tags, and reports errors per row |

### 🔷 System Settings
| Feature | Details |
|---------|---------|
| **Application Name** | Customize the app name displayed in the UI |
| **Timezone** | Select from a list of global timezones |
| **Company Name** | Set your company name |
| **Warranty Notice Period** | Days before warranty expiry to trigger notifications |
| **Auto Backup** | Enable/disable automatic database backups |
| **Backup Interval** | Daily, weekly, or monthly backup schedule |
| **One-Click Backup** | Manual database backup with download |
| **Backup Retention** | Automatic cleanup of backups older than 30 days |

### 🔷 IT Ticketing (IT Concerns)
| Feature | Details |
|---------|---------|
| **Ticket Submission (User)** | Users submit IT concerns with department auto-detected from their account, priority, description, and optional image attachment |
| **Ticket Numbering** | Auto-generated per-department ticket numbers (e.g., `FINISHING-20260811-001`) |
| **Admin Accept Workflow** | `PENDING → ACCEPTED → (repaired) → COMPLETED`, with `CANCELED` as a side exit |
| **Repair Stopwatch** | Accepting a ticket starts an elapsed-time timer (`now − accepted_at`, server-clock anchored). It counts up on the Manage page, updating every second |
| **Repaired By Assignment** | The admin who accepts the ticket is automatically recorded as the person responsible (`accepted_by(_id)` / `repaired_by(_id)` — stored by user ID, rename-safe). Completing the ticket later never overwrites this |
| **Actual Repair Time** | When the admin clicks *Mark as Repaired*, `repaired_date` is stored and the actual repair time (`repaired_date − accepted_at`) is shown in the ticket history |
| **Duplicate Protection** | Accept is an atomic conditional update (`active → accepted`); rapid double-clicks or replayed requests are rejected safely with a single timer created |
| **Cancel** | Admins can cancel pending or accepted tickets with a reason (cancel reason, date, and actor recorded). Cancelled tickets are never deleted |
| **Ticket Status Model** | `active` (Pending), `accepted` (Accepted / In Progress), `repaired` (Completed), `canceled` (Cancelled) |
| **My Active Concerns** | Users see live status of their tickets, including "Accepted — In Progress" and repair start time |
| **My Ticket History** | Users see all their tickets with per-ticket status, resolution details, and cancel reasons |
| **Admin Manage View** | Pending/Accepted stat cards, department filter, live elapsed timers, Accept / Mark as Repaired / Cancel actions |
| **Ticket History & Remarks** | Admin history of completed/cancelled tickets with search, department/status/period filters, editable remarks, acceptance records and actual repair time |
| **Ticket Analytics** | Monthly ticket volume chart with per-year filtering, averages, CSV export. Cancelled tickets are excluded from operational analytics |
| **Trouble Notifications** | In-app notifications when tickets are submitted, accepted, repaired, or cancelled |
| **Email Notifications** | Admin e-mail alerts on new tickets via an async mail queue (SMTP), processed non-blocking |
| **Security** | Admin-only accept/complete/cancel (RBAC) with CSRF token validation and double-submit guards; all timestamps come from the server, never the client |

### 🔷 Internal Messaging
| Feature | Details |
|---------|---------|
| **Direct Messages** | One-to-one conversations between users with an unread badge in the sidebar |
| **Conversation View** | Chat-style thread view with polling for new messages |
| **Unread Counter** | Live unread message polling |

### 🔷 System Startup Experience
| Feature | Details |
|---------|---------|
| **Startup Sequence** | Animated "system initializing" boot screen after login, redirecting admins/viewers to the dashboard and users to their tickets |

### 🔷 Database Backup
| Feature | Details |
|---------|---------|
| **mysqldump Support** | Uses mysqldump for optimal backups when available |
| **PHP Fallback** | Pure PHP-based backup when mysqldump is not installed |
| **Auto-Retention** | Automatically removes backups older than 30 days |
| **Audit Logging** | Logs each backup creation to the audit trail |
| **Download** | Serves backup file as a downloadable SQL file |

### 🔷 API (RESTful JSON)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/assets` | GET | Search/list assets (with query, type, status, department filters) |
| `/api/assets/{id}` | GET | Get single asset details with relations |
| `/api/users` | GET | Search users (autocomplete-friendly) |
| `/api/departments` | GET | List all departments |
| `/api/stats` | GET | Full dashboard statistics (counts, charts, activity) |
| `/api/topology/positions` | POST | Save topology node positions |

### 🔷 User Interface
| Feature | Details |
|---------|---------|
| **Responsive Design** | Optimized for desktop, tablet, and mobile |
| **Dark Mode** | System-preference-aware dark/light theme with toggle |
| **Sidebar** | Collapsible navigation with active-page highlighting |
| **Global Search** | Search bar in header for quick asset lookup |
| **Flash Messages** | Toast-style success/error/info/warning notifications |
| **Modern UI** | Font Awesome icons, smooth animations, glassmorphism auth pages |
| **Pagination** | Server-side pagination across all list views |

### 🔷 Security
| Feature | Details |
|---------|---------|
| **CSRF Protection** | Token validation on all POST/PUT/DELETE requests |
| **XSS Prevention** | `htmlspecialchars()` escaping on all output |
| **SQL Injection Prevention** | 100% PDO prepared statements — no raw queries |
| **Password Hashing** | bcrypt with configurable cost factor (default 12) |
| **Rate Limiting** | Login attempt throttling (5 attempts / 15 min lockout) |
| **Session Security** | HttpOnly, SameSite=Lax cookies, periodic regeneration |
| **Role-Based Access Control** | Middleware-enforced permissions on every route |
| **Input Validation** | Validated on all controller actions |
| **File Upload Validation** | Type, size, and extension checks |
| **Security Headers** | X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy |

---

## Requirements

- **PHP 8.0** or higher
- **MySQL 5.7+** / MariaDB 10.3+
- **Apache** with mod_rewrite (or IIS with URL Rewrite)
- PDO PHP Extension
- MySQLi PHP Extension
- JSON PHP Extension
- GD PHP Extension (for future image handling)

## Installation

### 1. Clone/Extract Files

Extract the files to your web server directory (e.g., `c:\xampp\htdocs\itassets`).

### 2. Create Database

Open phpMyAdmin or MySQL CLI and run:

```sql
CREATE DATABASE IF NOT EXISTS itassets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import Schema

Run the database schema:

```bash
mysql -u root -p itassets < database/schema.sql
```

Or import `database/schema.sql` via phpMyAdmin.

### 4. Import Seed Data (Optional)

```bash
mysql -u root -p itassets < database/seed.sql
```

### 5. Configure Database

Edit `config/database.php` and update the connection settings:

```php
return [
    'host'     => 'localhost',
    'port'     => '3306',
    'database' => 'itassets',
    'username' => 'root',
    'password' => '',
    // ...
];
```

### 6. Set Permissions

Ensure the following directories are writable:

- `storage/logs/`
- `storage/backups/`
- `storage/cache/views/`
- `public/uploads/avatars/`

### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/itassets
```

### Default Credentials

| Role     | Username   | Password  |
|----------|------------|-----------|
| Admin    | admin      | admin123  |
| Users    | user       | admin123  |
| Viewer   | viewer     | admin123  |

**⚠️ IMPORTANT:** Change the default admin password immediately after first login.

## Folder Structure

```
itassets/
├── index.php                                # Front controller (entry point)
├── .htaccess                                # URL rewriting (Apache mod_rewrite)
├── backup.php                               # Standalone backup script
├── config/                                  # Application configuration
│   ├── app.php                              # App settings (version, session, auth, backup)
│   ├── constants.php                        # Paths, URLs, global constants
│   └── database.php                         # Database connection settings
├── public/                                  # Public assets (CSS, JS, images)
│   ├── assets/
│   │   ├── css/                             # Stylesheets (app.css, auth.css, topology.css)
│   │   ├── js/                              # JavaScript (app.js, dashboard.js, topology.js, theme.js)
│   │   └── img/devices/                     # Device type SVG icons
│   └── uploads/avatars/                     # User avatar uploads
├── src/                                     # Application source code
│   ├── Core/                                # Framework core
│   │   ├── Controller.php                   # Base controller
│   │   ├── Database.php                     # PDO singleton
│   │   ├── Middleware.php                   # Base middleware
│   │   ├── Model.php                        # Active Record base model
│   │   ├── Request.php                      # HTTP request handler
│   │   ├── Response.php                     # HTTP response handler
│   │   ├── Router.php                       # Front controller router
│   │   ├── Session.php                      # Session manager
│   │   ├── Validator.php                    # Input validation
│   │   └── View.php                         # View renderer
│   ├── Controllers/                         # Route controllers
│   │   ├── ApiController.php                # REST API endpoints
│   │   ├── AnalyticsController.php          # Ticket analytics dashboard/export
│   │   ├── AssetController.php              # Asset CRUD + import/export
│   │   ├── AuthController.php               # Login, logout, profile
│   │   ├── DashboardController.php          # Dashboard stats/charts
│   │   ├── DepartmentController.php         # Department CRUD
│   │   ├── DisposalController.php           # Asset disposal workflow
│   │   ├── HistoryController.php            # Audit log viewer
│   │   ├── IncrementalBackupController.php  # Ticket backup/export
│   │   ├── InstructionsController.php       # IT instructions (admin)
│   │   ├── LicenseController.php            # Software licenses
│   │   ├── MailQueueController.php          # Mail queue status/processing
│   │   ├── MessageController.php            # Internal messaging
│   │   ├── SettingsController.php           # System settings
│   │   ├── StartupController.php            # Post-login startup sequence
│   │   ├── TicketController.php             # Ticket CRUD, accept/complete/cancel
│   │   ├── TicketExportController.php       # Ticket CSV export
│   │   ├── TicketHistoryController.php      # Ticket history & remarks
│   │   ├── TopologyController.php           # Network topology
│   │   └── UserController.php               # User management (admin)
│   ├── Models/                              # Database models
│   │   ├── Asset.php
│   │   ├── AssetDisposal.php
│   │   ├── AssetHistory.php
│   │   ├── Concern.php                      # IT concern / ticket (accept, timer, statuses)
│   │   ├── Department.php
│   │   ├── Instruction.php
│   │   ├── LicenseAssignment.php
│   │   ├── Message.php
│   │   ├── NetworkLink.php
│   │   ├── Setting.php
│   │   ├── SoftwareLicense.php
│   │   ├── TicketSequence.php              # Per-department ticket number generation
│   │   ├── TroubleNotification.php
│   │   └── User.php
│   ├── Services/                           # Business logic
│   │   ├── AuditService.php                # Audit logging
│   │   ├── AuthService.php                 # Authentication (+ remember-me)
│   │   ├── DiagramService.php              # Topology data
│   │   ├── ExportService.php               # CSV import/export
│   │   ├── MailQueueService.php            # Async SMTP queue
│   │   ├── MailService.php                 # SMTP sending
│   │   └── TicketService.php               # Ticket submit/accept/complete/cancel
│   ├── Middleware/                         # Route middleware
│   │   ├── AuthMiddleware.php              # Authentication check
│   │   ├── CsrfMiddleware.php              # CSRF token validation
│   │   └── RoleMiddleware.php              # Role-based access control
│   ├── Helpers/                            # Utility classes
│   │   ├── File.php
│   │   ├── Format.php
│   │   └── Security.php                    # CSRF, XSS, password, rate limiting
│   └── Exceptions/                         # Custom exceptions
│       ├── AuthException.php
│       ├── NotFoundException.php
│       └── ValidationException.php
├── views/                                  # HTML templates (PHP)
│   ├── layouts/                            # Base layouts
│   │   ├── main.php                        # Main app layout (sidebar + header)
│   │   ├── auth.php                        # Login layout (centered card)
│   │   └── error.php                       # Error page layout
│   ├── auth/                               # Login/Profile pages
│   ├── dashboard/                          # Dashboard view
│   ├── assets/                             # Asset management views
│   ├── departments/                        # Department management
│   ├── disposals/                          # Asset disposal views
│   ├── history/                            # Audit log
│   ├── licenses/                           # Software license views
│   ├── messages/                           # Internal messaging
│   ├── settings/                           # System settings
│   ├── startup/                            # Post-login startup animation
│   ├── tickets/                            # Ticketing (user + admin + history + analytics)
│   ├── topology/                           # Network diagram view
│   ├── users/                              # User management
│   └── partials/                           # Reusable components
├── database/                               # SQL schema, seeds, and migrations
│   ├── schema.sql
│   ├── seed.sql
│   ├── reset.sql
│   └── migrations/                         # Numbered schema migrations (001–017) + recent ticket-workflow migrations
├── storage/                                # Logs, backups, cache
│   ├── backups/
│   ├── cache/views/
│   └── logs/
└── tests/                                  # Unit and integration tests
```

## User Roles

| Role | Permissions |
|------|------------|
| **Admin** | Full access — manage assets, users, departments, settings, view audit logs, run backups, and manage the ticket queue (accept, mark repaired, cancel) |
| **Users** | Submits and tracks their own IT tickets, can manage assets and departments, view topology, dashboard, audit log. Lands on their ticket page after login |
| **Viewer** | Read-only access to all views including topology and dashboard |

## Ticket Workflow

```text
USER SUBMITS TICKET
        ↓
     PENDING (active)
        ↓            └──► CANCELLED (canceled, with reason — kept for audit)
  ADMIN ACCEPTS
        ↓
   ACCEPTED (accepted)   ← accepting admin is recorded as "Repaired By"
        ↓
  REPAIR TIMER RUNS      ← elapsed time = now − accepted_at (server clock)
        ↓
  MARK AS REPAIRED
        ↓
    COMPLETED (repaired)  ← actual repair time = repaired_date − accepted_at
```

## Security Features

- ✅ CSRF Protection on all forms
- ✅ XSS Prevention (output escaping)
- ✅ SQL Injection Prevention (PDO prepared statements)
- ✅ Password Hashing (bcrypt with cost factor 12)
- ✅ Rate Limiting on login attempts
- ✅ Session Security (HttpOnly, SameSite cookies, periodic regeneration)
- ✅ Role-Based Access Control (RBAC)
- ✅ Input Validation on all forms
- ✅ Secure File Upload Validation
- ✅ Audit Logging for all CRUD operations
- ✅ Security Headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection)

## Troubleshooting

### Blank page on access
- Check PHP error logs in `storage/logs/`
- Ensure `display_errors` is enabled in `config/constants.php` (debug mode)
- Verify database connection settings

### 404 on all pages
- Ensure Apache mod_rewrite is enabled
- Check `.htaccess` file exists
- Verify AllowOverride is set to All in Apache config
- **URL casing**: the app can be accessed with any letter casing (e.g. `/Itara` or `/itara`) — routing strips the base path case-insensitively, so mixed-case URLs do not produce 404s

### Database connection error
- Verify MySQL/MariaDB is running
- Check credentials in `config/database.php`
- Ensure database 'itassets' exists

### Network diagram not loading
- Check browser console for JavaScript errors
- Ensure assets have position data (auto-layout available)
- Verify Chart.js is loading from CDN

## License

MIT License — see LICENSE file for details.

## Support

For issues and feature requests, please contact the IT department.
