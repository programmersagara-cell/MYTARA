# ITSaAMS Deployment Documentation

> **IT Service and Asset Management System**
> PHP 8+ · MySQL 5.7+/MariaDB 10.3+ · Apache with mod_rewrite

This directory contains deployment documentation for ITSaAMS.

## Documentation Index

| Document | Description |
|----------|-------------|
| [README.md](./README.md) | This file — documentation index |
| [deployment.md](./deployment.md) | Comprehensive deployment guide |
| [quickstart.md](./quickstart.md) | Quick start deployment for development/local |
| [configuration.md](./configuration.md) | Configuration reference and settings |
| [troubleshooting.md](./troubleshooting.md) | Common issues and solutions |
| [updates.md](./updates.md) | Update and migration procedures |
| [security.md](./security.md) | Security considerations for deployment |

## Quick Links

- **Getting started?** → See [quickstart.md](./quickstart.md)
- **Deploying to production?** → See [deployment.md](./deployment.md)
- **Configuration questions?** → See [configuration.md](./configuration.md)
- **Having issues?** → See [troubleshooting.md](./troubleshooting.md)
- **Updating the app?** → See [updates.md](./updates.md)

## Prerequisites

Before deploying ITSaAMS, ensure you have:

- PHP 8.0 or later
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4 with mod_rewrite (or equivalent web server configuration)
- Access to create databases and users
- Appropriate file system permissions

## Deployment Overview

ITSaAMS is a PHP-based web application with the following deployment characteristics:

- **No build step required** — PHP files are deployed directly
- **Front controller architecture** — All requests routed through `index.php`
- **Database-driven** — Requires MySQL/MariaDB database
- **Session-based authentication** — Uses PHP sessions with secure cookie settings
- **File upload support** — Requires writable upload directories
- **Email functionality** — Optional SMTP configuration for notifications

## Support

For issues not covered in these documents, check:
- Application logs in `storage/logs/`
- Web server error logs
- The main README.md in the project root
