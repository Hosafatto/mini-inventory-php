# Mini Inventory Manager

A small inventory management web app built with **PHP 8.4/8.5**, **PDO**, and **SQLite**. Created as a personal PHP coding sample demonstrating CRUD operations, SQL queries, form validation, and simple web UI development.

## Features
- Add, edit, list, search, and delete products
- Unique SKU, nonnegative prices and stock quantities
- Prepared SQL statements and output escaping
- CSRF protection for write operations
- Responsive interface without a frontend framework
- Automatically initializes a local SQLite database

## Requirements
- **Patched PHP 8.4.27+ or PHP 8.5.12+** (within the respective supported release series), with `pdo_sqlite`, `sqlite3`, and `mbstring` extensions enabled. PHP 8.4 is the tested target; PHP 8.5 compatibility should be verified locally.

## Windows setup
1. Download the current patched PHP 8.4 x64 Windows build from https://windows.php.net/download/ . Avoid outdated installers or pre-release builds.
2. Extract to `C:\php84` and add that folder to your user PATH.
3. Copy `php.ini-development` to `php.ini` and enable `extension=pdo_sqlite`, `extension=sqlite3`, and `extension=mbstring` (uncomment those lines).
4. Open a new PowerShell terminal and verify:
```powershell
php -v
php -m | findstr /i "pdo_sqlite sqlite3 mbstring"
```

## Run locally
```bash
cd mini-inventory-php
php -S localhost:8000 -t public
```
Open http://localhost:8000 in your browser. The SQLite database is created in `storage/` automatically on first request. Ensure `storage/` is writable.

## Project structure
```
public/index.php          # Routes, validation, HTML UI
public/assets/style.css   # Responsive styles
src/db.php                # PDO connection and schema initialization
storage/.gitkeep          # Writable database directory
```

## Security and scope
Run the built-in PHP development server **only on localhost**, not on a public interface. The `storage/` folder is outside the document root and ignored by Git. All write requests require a session CSRF token. SQL writes use prepared statements. HTML output is escaped. Do not deploy publicly without authentication, rate limiting, logging, and a real web-server configuration. Keep PHP patched.

## Notes
This is a small learning/portfolio project, not a production-ready multi-user inventory system. It deliberately avoids a framework and external packages to keep the core PHP implementation easy to inspect. For production use, add authentication, pagination, stronger domain validation, structured error logging, automated tests, and a database migration workflow.

## Author
José Salazar — personal portfolio project.

## Smoke test
Run `php tests/smoke.php` after configuring extensions. This creates and deletes a temporary test product.
