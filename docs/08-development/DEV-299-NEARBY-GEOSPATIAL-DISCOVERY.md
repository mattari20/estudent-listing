# DEV-299 — Nearby Geospatial Discovery

**Status:** Implemented on branch; pending merge into `main`  
**Scope:** Institutes Directory nearby campus discovery

## 1. Objective

Add a bounded, read-only nearby discovery capability to the Institutes Directory.

The feature answers a discovery question such as:

> Which active institutes have an active campus near these coordinates?

It does not modify canonical geography or institution data.

## 2. Public API

The endpoint is:

`GET /api/v1/institutions/nearby`

Required parameters:

- `lat`
- `lng`

Optional parameters:

- `radius_km` — default 25 km; maximum 100 km
- `limit` — default 24; maximum 100

The route is registered before the slug route so `nearby` is treated as an endpoint rather than an institution slug.

## 3. Public Eligibility Boundary

A nearby result requires:

- active institution;
- active campus;
- non-null campus latitude;
- non-null campus longitude;
- active assigned geography.

Inactive or incomplete campus geography is excluded.

## 4. Distance Model

The service:

1. applies a latitude/longitude bounding box to reduce the candidate set;
2. calculates great-circle distance using the Haversine formula;
3. removes candidates outside the requested radius;
4. sorts by distance;
5. returns at most one result per institution.

When an institution has multiple qualifying campuses, the nearest qualifying campus represents that institution in the result.

## 5. Validation and Limits

Coordinates are validated to their legal geographic ranges.

Radius is limited to 100 km and result count to 100.

These limits are intentional safeguards against accidental unbounded geospatial queries.

## 6. Read-Only Safety

The feature performs no:

- geography reassignment;
- coordinate correction;
- institution activation/deactivation;
- campus mutation;
- canonical data repair.

## 7. Compatibility

The existing collection and profile API endpoints remain unchanged.

Nearby discovery is an additive API capability.

## 8. Testing

Regression coverage verifies:

- distance ordering;
- exact-location zero distance;
- exclusion of inactive institutions;
- exclusion of inactive campuses;
- exclusion of inactive geography;
- invalid coordinate rejection;
- radius limit enforcement;
- institution deduplication using the nearest campus.

## 9. Scaling Boundary

This implementation deliberately uses a bounding-box prefilter before Haversine calculation.

A future high-volume implementation may introduce a database-native spatial index or other optimized geospatial storage/query mechanism, but that is not required for the current bounded API contract.

The public contract should remain stable if the internal distance-query implementation is later optimized.

## 10. Non-Goals

This batch does not implement:

- user GPS permission handling;
- browser geolocation UI;
- maps;
- routing/directions;
- travel-time estimates;
- personalized recommendations;
- ranking;
- automatic geocoding;
- coordinate discovery from external providers.

Those concerns require separate governed work.

## 11. Governance

This implementation follows the Institutes Directory API/data-access governance and the existing campus/geography integrity boundaries.

Nearby discovery is a derived read operation over canonical campus coordinates; it does not become a new source of truth.
