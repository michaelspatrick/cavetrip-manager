# ADR 005: Admin-Only Cave Records

## Status

Accepted

## Context

Cave location, access, gate, and sensitive notes must be protected. Guests should never be able to browse cave records. Members may later receive carefully scoped trip-specific access, but unrestricted cave management should remain admin-only.

## Decision

In v0.5.0, cave list/create/edit screens require `super_admin` or `grotto_admin`.

Trip workflow will later expose only the information needed by an authorized trip leader for their own trip.

## Consequences

This keeps the early implementation safe while leaving room for more nuanced trip-leader access controls.
