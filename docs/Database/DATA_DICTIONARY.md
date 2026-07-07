# Data Dictionary

This document is the source of truth for table and field names.

## Naming Rules

- Database tables use `snake_case`.
- Database columns use `snake_case`.
- PHP variables use `camelCase`.
- PHP classes use `PascalCase`.
- IDs use `{entity}_id`, for example `trip_id`, `user_id`, `grotto_id`.

## Core Tables

### grottos

Stores each grotto/organization using the system.

### users

Stores user accounts. Users may be admins, members, or guests.

### landowners

Stores landowner contact and relationship information.

### caves

Stores cave records and sensitive access/location information.

### trips

Central object for trip planning, signup, waivers, callouts, and reports.

### trip_participants

Stores participant signup, waiver, emergency contact, and medical information.

### waiver_templates

Stores reusable waiver HTML templates.

### generated_waivers

Stores finalized rendered waivers.

### audit_log

Stores important system actions.
