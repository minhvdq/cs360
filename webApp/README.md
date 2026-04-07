# Simple PHP Auth App (PDO)

This folder contains a basic authentication flow using **PDO prepared statements** for all database interactions.

## Files

- `config.php` - PDO database connection settings
- `register.php` - user registration (INSERT with `SHA2(?, 256)`)
- `login.php` - user login (SELECT with `SHA2(?, 256)`)
- `dashboard.php` - protected page (session required)
- `logout.php` - session termination

## Database Schema

```sql
CREATE DATABASE IF NOT EXISTS webapp_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE webapp_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash CHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Database User and GRANT Statements

```sql
CREATE USER IF NOT EXISTS 'webapp_user'@'localhost' IDENTIFIED BY 'change_me';

GRANT SELECT, INSERT, UPDATE, DELETE
ON webapp_db.*
TO 'webapp_user'@'localhost';

FLUSH PRIVILEGES;
```

## Notes

- Update credentials in `config.php` to match your environment.
- Passwords are compared and stored using MySQL `SHA2(..., 256)` directly in prepared SQL queries.
- Error/status messages are shown with query parameters, for example:
  - `login.php?error=invalid_credentials`
  - `login.php?error=not_logged_in`
  - `register.php?error=username_taken`
