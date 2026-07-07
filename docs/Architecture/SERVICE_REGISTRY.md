# Service Registry

This document prevents duplicate helper functions and duplicate service responsibilities.

## Rule

Before adding a new helper or service, check this registry first.

## Services

### MigrationService

Owns database migration execution.

### NotificationService

Reserved for future email, Pushover, and system notifications.

### TripService

Reserved for trip creation, updates, cancellation, callout status, and trip workflow actions.

### WaiverService

Reserved for template rendering, waiver generation, finalization, and permanent waiver archive logic.

### SignatureService

Reserved for signature validation, storage, and rendering.

### AuditLogService

Reserved for recording important system events.

### PermissionService

Reserved for role and access-control checks.
