# CaveTrip Manager

**Plan. Explore. Return Safely.**

CaveTrip Manager is an open-source, multi-grotto platform for cave trip planning, digital waivers, landowner records, participant safety, emergency callouts, rescue packets, and trip reports.

## Current release

This package is the early admin foundation release. It includes:

- PHP 8.1+ project skeleton
- Environment loading from `.env`
- PDO database connection
- Simple route/view layer
- CLI migration runner
- Initial migrations for grottos, users, caves, landowners, trips, waivers, participants, callouts, emergency packets, reports, invites, notification settings, and audit log
- Starter public page
- Login/logout and session authentication
- Initial dashboard
- Grotto settings page with logo URL/upload support
- User creation/listing scaffolding
- Health check endpoint
- Architecture and design documentation

## Requirements

- PHP 8.1+
- MySQL 8 / Percona MySQL / MariaDB compatible database
- PDO MySQL extension
- Composer is recommended, but the app includes a fallback PSR-4 autoloader for early development

## Local setup

```bash
cp .env.example .env
```

Edit `.env` with your database credentials.

Create the database:

```sql
CREATE DATABASE cavetrip_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations:

```bash
php migrate.php
```

Start PHP's built-in development server:

```bash
php -S localhost:8080 -t public
```

Open:

```text
http://localhost:8080
```

Health check:

```text
http://localhost:8080/health
```

## Git workflow

Suggested first commit:

```bash
git add .
git commit -m "Add CaveTrip Manager admin settings foundation"
git tag v0.4.0-admin-settings
```

## License

GPL-3.0-or-later.


## First admin user

After configuring `.env` and running migrations, create an initial admin account:

```bash
php tools/create_admin.php   --name="Your Name"   --email="you@example.com"   --password="CHANGE_ME_STRONG_PASSWORD"   --grotto-name="Six Ridges Grotto"   --grotto-slug="six-ridges"
```

Then visit `/login`. After signing in, use `/dashboard` for the admin landing page and `/admin/grotto/settings` for grotto branding/contact settings.

## v0.6.0 Trip Foundation

After uploading this release and running migrations, visit:

- `/trips`
- `/trips/create`

This release uses the existing `trips` table from the early schema and adds the first user-facing trip management screens.


## Current development release

### v0.7.0 Participants + UI Polish

Adds participant roster management, public trip signup links, emergency/medical fields, and improved UI styling for dashboards and forms.
