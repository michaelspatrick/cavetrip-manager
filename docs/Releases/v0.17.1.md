# CaveTrip Manager v0.17.1 — Schema and UI Hotfix

This is a repair release for v0.16/v0.17 installations.

## Fixed

- Repairs missing `caves.state` column.
- Adds cave fields already expected by `CaveService`: `access_directions`, `parking_notes`, and `gate_code`.
- Removes legacy `general_area`, `gps_latitude`, and `gps_longitude` fields if they still exist.
- Creates the missing `trip_callout_contacts` table.
- Repairs the Trip Reports schema conflict caused by the original CaveTrip Manager foundation already having a different `trip_reports` table.
- Preserves the original report table as `trip_reports_legacy_v1` and imports compatible legacy reports into the new schema.
- Fixes action-link styling for Create Trip, Add Landowner, Add Template, Create User, and similar `.button` links.
- Forces the desktop sidebar/admin shell layout and bumps the CSS cache key.

## Install

From the extracted release directory:

    ./scripts/apply-release.sh /opt/cavetrip-manager
    cd /opt/cavetrip-manager
    php -l database/migrations/20260902_171000_schema_repair.php
    php -l app/Views/layouts/app.php
    git diff
    git add .
    git commit -m "Repair v0.17 schema and admin button styling"
    git push origin main
    sudo /var/www/trips.sixridgesgrotto.org/update.sh

The deployment script must run `php migrate.php` before testing the affected pages.

## Verify after deployment

    mysql -u root cavetrip_manager -e "SHOW COLUMNS FROM caves; SHOW TABLES LIKE 'trip_callout_contacts'; SHOW COLUMNS FROM trip_reports;"

Then test:

- `/caves`
- `/trip-reports`
- `/trips/show?id=<trip id>`
- `/trips`
- `/landowners`
- `/waiver-templates`
- `/users`

If the browser still shows old button styling, hard refresh once (Ctrl+F5). The release changes the stylesheet URL to `?v=0.17.1`, so normal cache busting should already occur.
