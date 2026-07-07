# ADR 004: Database User Accounts

## Status
Accepted

## Context

The project started with the idea of using `.htaccess` protection. That is simple, but it does not support different permissions for admins, members, trip leaders, and guests.

## Decision

CaveTrip Manager will use database-backed user accounts and application-level role checks.

Initial roles:

- `super_admin`
- `grotto_admin`
- `member`
- `guest`

## Consequences

The application can protect sensitive cave and landowner information while still allowing tokenized guest trip signup. It also supports future features such as converting guests to members, per-grotto admins, audit logs, and trip leader permissions.
