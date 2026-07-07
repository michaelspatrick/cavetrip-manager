# ADR 001: Multi-Grotto Architecture

## Status
Accepted

## Decision
CaveTrip Manager will support multiple grottos in one installation. Major operational tables will include `grotto_id`.

## Rationale
The project is intended to be open-source and useful beyond one grotto. Multi-grotto separation allows either private single-grotto installs or hosted multi-grotto deployments.

## Consequences
Every query involving grotto-scoped data must filter by `grotto_id`. Permission checks must prevent cross-grotto data exposure.
