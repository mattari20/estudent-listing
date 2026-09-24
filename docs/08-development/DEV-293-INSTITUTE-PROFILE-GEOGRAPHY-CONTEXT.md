# DEV-293 — Institute Profile Geography Context

## Purpose

Expose a safe root-to-leaf geography context on public institute profiles.

## Behaviour

Active campuses receive a human-readable geography path such as:

`Punjab · Lahore District · Lahore`

The path is only returned when the campus and every traversed ancestor are active.

## Safety

Inactive geography, inactive campuses, and cyclic geography ancestry produce no public context. The service is read-only and does not repair canonical relationships.

## Integration

The institution profile loads only active campus geography and renders the computed context instead of exposing an inactive or partial geography path.

## Testing

Coverage includes valid hierarchy, inactive ancestor rejection, cycle rejection, non-mutation, and inactive campus exclusion.
