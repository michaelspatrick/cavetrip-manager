# Naming Standards

## Database

Use `snake_case` for tables, columns, indexes, and foreign keys.

Examples:

- `trip_id`
- `grotto_id`
- `callout_time`
- `waiver_template_id`

## PHP

Use `camelCase` for variables and methods.

Examples:

- `$tripId`
- `$grottoId`
- `generateTripNumber()`

Use `PascalCase` for classes.

Examples:

- `TripService`
- `MigrationService`
- `AuditLogService`

## Rule

Once a field, enum, service, or method name is accepted into the SDS or Data Dictionary, it becomes the source-of-truth name. Do not create alternate names for the same concept.
