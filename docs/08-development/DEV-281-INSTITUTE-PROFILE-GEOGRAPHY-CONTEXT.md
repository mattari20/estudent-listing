# DEV-281 — Geography Context in Institute Profiles

## Status

Implemented on branch `dev-281-profile-geography-context`.

## Purpose

Expose a clear root-to-leaf geography context on public institute profiles while preventing inactive geography from being presented as current.

## Rules

- Public profiles show geography context only for active campuses.
- The assigned geography must be active.
- Geography ancestors must also be active.
- Context is displayed from root to leaf, for example: `Punjab · Lahore District · Lahore`.
- If the hierarchy is incomplete, inactive, or cyclic, no incomplete context is displayed.
- Existing campus, address, and program data remain unchanged.

## Safety

This is a read-only presentation boundary. It does not repair geography, change status, or reassign campuses.
