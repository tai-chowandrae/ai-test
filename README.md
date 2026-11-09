# Project Work Agreements

## Technical Stack

| Component | Technology |
| --- | --- |
| Backend | PHP 8.0 or higher with PDO |
| Database | MySQL 5.7 or higher, or MariaDB 10.3 or higher |
| Database Extension | pdo_mysql |
| Web Server | Apache with mod_rewrite |
| API | REST API through `api/index.php` |
| Frontend | Vanilla JavaScript, no frameworks |

## Security

- Public hosts redirect HTTP to HTTPS. Install a valid TLS certificate before deployment; localhost, 127.0.0.1 and ::1 remain available over HTTP.
- Session cookies use HttpOnly and SameSite=Lax, plus Secure on HTTPS. HTTPS responses enable HSTS. TLS detection uses the web server's HTTPS variable; configure trusted TLS termination at the host if using a reverse proxy.
- Every authenticated request checks the account's current active status and admin role. Deleted or inactive accounts lose access on their next request. Already displayed pages cannot be remotely erased.
- All POST API actions require a session CSRF token. Forms and JavaScript requests supply it automatically.
- Login allows 20 submissions per IP and 10 per email in 15 minutes; registration allows 5 per IP and 3 per email. Limits apply across browser sessions, including successful submissions, and reset after the window expires. Client-supplied forwarding headers are not trusted.
- PHP must be able to create and write `storage/auth-attempts`. Counters use hashed keys and file locking; no database migration is needed. Do not publish runtime counter files. Old counters may be removed during maintenance when more than a day old.
- Apache denies direct access to internal directories, dotfiles, backups and the database test page. Keep the directory `.htaccess` files deployed and allow Apache overrides.
- Run `php tests/security.php` against the local Apache site at `http://localhost` with the local database configured. Requires PHP curl. The test creates and removes a disposable user and checks rate limits, expiry, CSRF, protected files and session revocation.

## Local Database Defaults

| Setting | Value |
| --- | --- |
| Database | `ai-test` |
| Username | `root` |
| Password | Empty |

## Google Maps

- Store the Google Maps API key in `config/GoogleMaps.php`.
- Locations are saved with a Google Place ID, formatted address, and optional latitude/longitude.
- Driving distance for trip registrations is calculated server-side through Google Routes API.
- Enable the required Google Maps Platform APIs for the key: Maps JavaScript API, Places API, and Routes API.
- Existing databases must run the SQL migrations in `database/AddIsAdminToUsers.sql`, `database/AddLocationsAndTripRegistrations.sql`, `database/AddTripDescriptions.sql`, `database/AddCommuteCompensationToTripRegistrations.sql`, and `database/AddIsActiveToLocations.sql`.

## Project Structure

Static assets must be placed in separate folders and should not be stored directly in the project root.

```text
/
+-- api/
|   +-- index.php
+-- config/
+-- database/
+-- css/
+-- js/
+-- images/
+-- admin.php
+-- dashboard.php
+-- login.php
+-- register.php
+-- README.md
```

The project root should only contain required entry points and project-level files.

## Pages And Routing

- Use separate pages for separate user flows, such as `Register` and `Login`.
- Do not store all application screens in one single page.
- Hide file extensions in public URLs.
- Use SEO-friendly URLs through Apache `mod_rewrite`.
- Public URLs should be readable, lowercase, and hyphen-separated.
- The user dashboard must be designed mobile-first and used primarily on a mobile phone.
- The admin page must be designed desktop-first and used primarily on a desktop computer.

Example URL structure:

```text
/login
/admin
/dashboard
/register
```

## Naming And Language

- Use PascalCase for project-defined names.
- Use PascalCase for database names, table names, column names, indexes, and foreign keys.
- Write all application logic, identifiers, functions, classes, API fields, SQL comments, and technical comments in English.
- Add comments where they clarify intent or explain non-trivial logic.
- User-facing interface text may be Dutch unless agreed otherwise.

## Database Naming Example

```sql
CREATE TABLE Users (
  UserId INT AUTO_INCREMENT PRIMARY KEY,
  EmailAddress VARCHAR(255) NOT NULL,
  PasswordHash VARCHAR(255) NOT NULL,
  IsAdmin TINYINT(1) NOT NULL DEFAULT 0,
  CreatedAt DATETIME NOT NULL
);
```
