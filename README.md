# Flight Management System

Database-driven flight management system built with **Vanilla PHP**, **MySQL**, and **HTML/CSS/JS**.

## Features

- **Admin Login:** Access admin panel with credentials
- **Administrators:** Manage flights (add/delete flights)
- **Passengers:** Login functionality (basic access)

## Project Structure

```
DBproject/
├── admin/              Admin flight management
├── assets/             CSS and JavaScript
├── config/             Database configuration
├── database/           MySQL DDL and stored procedures
├── includes/           Auth, DB helpers, layout
├── index.php
├── login.php
├── dashboard.php
└── logout.php
```

## Setup

### 1. MySQL Database

Requirements: **MySQL 8.0+** (or MariaDB 10.3+ with minor adjustments)

Run scripts in order:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/plsql.sql
```

Or import both files in phpMyAdmin / MySQL Workbench.

### 2. PHP Environment

Requirements:
- PHP 8.0+
- PDO MySQL extension (`pdo_mysql`)
- Apache/Nginx or XAMPP/WAMP

Copy credentials:

```bash
copy config\database.example.php config\database.local.php
```

Edit `config/database.local.php`:
- MySQL host, port, database name
- Username and password
- `base_path` — URL path where the app is hosted (default: `/DBproject`)

### 3. Web Server

Place the project in your web root (e.g. `C:\xampp\htdocs\demo`) and open:

```
http://localhost/demo/
```

## Demo Accounts

| Role      | Email                 | Password |
|-----------|-----------------------|----------|
| Admin     | admin@flightbook.com  | password |

## Security Notes

- Passwords hashed with `password_hash()` (bcrypt)
- All SQL uses prepared statements
- Database operations wrapped in try/catch blocks
- Client and server-side input validation
