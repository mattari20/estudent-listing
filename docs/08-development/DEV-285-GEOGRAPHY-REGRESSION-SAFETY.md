# DEV-285 — Geography Regression and Safety Testing

## Status

Implemented on branch `dev-285-geography-regression-safety`.

## Coverage

- Valid active institution/campus/geography remains clean.
- Invalid active campus remains detectable without mutation.
- Canonical campus status and geography assignment are unchanged by detection.
- Operator integrity review route is not publicly accessible or writable.

## Safety

The regression suite verifies that integrity detection remains observational and that operator review stays behind the existing authenticated operator boundary.
