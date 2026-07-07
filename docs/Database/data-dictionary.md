# Data Dictionary

This file is the source of truth for table and column names. Keep it synchronized with database migrations.

## Core ownership

Most operational records include `grotto_id` so one installation can serve multiple grottos without mixing data.

## Tables in v0.2.0

### grottos
Stores organization profile and branding information.

### users
Stores super admins, grotto admins, members, and guests.

### landowners
Stores landowner contact details and notes for one grotto.

### caves
Stores cave records. Sensitive fields must be protected by role and trip access checks.

### notification_settings
Stores email and Pushover configuration per grotto.

### audit_log
Stores important user/system actions.

### waiver_templates
Stores reusable HTML waiver templates.

### trips
Central trip record. Trips connect participants, caves, landowners, waivers, callouts, and reports.

### trip_participants
Stores trip signup records, signatures, emergency contacts, and medical safety notes.

### generated_waivers
Stores finalized waiver HTML and permanent public token.

### trip_callout_events
Stores all callout/safety status changes.

### emergency_packets
Stores restricted rescue packet tokens and access metadata.

### trip_reports
Stores the final trip report.

### trip_report_notes
Stores member-contributed notes before the final report.

### trip_invites
Stores selected-member and email invites.

### waiver_email_log
Stores delivery status for waiver emails.


## v0.5.0 Additions

### `landowners`

Operational contact records for cave/property owners. Records are scoped by `grotto_id`.

Important fields:

- `name`
- `email`
- `phone`
- `mailing_address`
- `preferred_contact_method`
- `notes`
- `active`

### `caves`

Admin-only cave records. Records are scoped by `grotto_id` and may optionally link to `landowners.id`.

Important fields:

- `name`
- `county`
- `general_area`
- `gps_latitude`
- `gps_longitude`
- `access_notes`
- `access_directions`
- `parking_notes`
- `gate_code`
- `sensitive_notes`
- `active`

Cave location and access fields are restricted and should not be exposed to guests or general public signup pages.
