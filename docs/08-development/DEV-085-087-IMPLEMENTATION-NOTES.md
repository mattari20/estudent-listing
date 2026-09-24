# DEV-085–087 — Implementation Notes

The reverification layer intentionally separates three actions:

1. opening/reviewing a stale-data task;
2. recording the evidence used to resolve that task;
3. explicitly refreshing governed canonical attributes.

A new evidence record is not required to overwrite historical evidence. Canonical refresh is operator-only and produces ChangeEvent records for each changed attribute.