# ADR 003: Use PHP Migration Files

## Status

Accepted.

## Decision

CaveTrip Manager will use ordered PHP migration files in `database/migrations`.

Each migration returns a callable that receives a `PDO` instance.

## Rationale

- Keeps database changes version-controlled.
- Avoids manual SQL upgrade instructions.
- Works without a large framework.
- Allows self-hosted grotto installations to upgrade predictably.

## Consequences

Every schema change must be added as a new migration. Existing migrations should not be edited after a public release except to fix a non-released development error.
