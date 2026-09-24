# DEV-287 — Automated CI Test Gate

## Status

Implemented on branch `dev-287-ci-test-gate`.

## Purpose

Establish the first repository-native automated quality gate for pull requests and pushes to `main`.

## Checks

- Install the locked Composer dependency graph available to the repository.
- Run Laravel Pint in check mode.
- Run the complete configured PHPUnit/Laravel test suite.
- Use SQLite for isolated CI database execution.

## Safety

This workflow only validates code. It does not deploy, mutate production data, merge pull requests, or change canonical directory records.
