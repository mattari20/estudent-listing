# DEV-321 — Application Validation & Local Testing Guide

## Purpose

DEV-321 defines the practical test path after repository finalization. GitHub validates the codebase automatically; localhost is used for interactive development and browser testing; a staging deployment is used for final real-server validation before production.

## 1. GitHub validation

The repository contains automated PHP/Composer validation workflows. The committed `composer.lock` must be used with `composer install`; dependencies must not be resolved differently on every run.

A workflow result must be observed in GitHub before it is described as passed. If GitHub marks a workflow `action_required`, the required GitHub approval/action must be completed before treating the run as executed.

## 2. Localhost validation

Recommended Windows setup:

- PHP 8.5
- Composer 2
- MySQL or MariaDB
- Node.js 22 if frontend assets are built locally
- Git

Clone the repository, copy `.env.example` to `.env`, configure a local database, install dependencies with `composer install`, generate the application key with `php artisan key:generate`, run migrations, and start the application with the Laravel development server or the selected local PHP server.

Do not copy production secrets into localhost.

## 3. Browser smoke test

After the local application starts, verify:

- `/institutes`
- `/institutes/search`
- `/institutes/login`
- an active institute profile
- `/institutes/sitemap.xml`
- `/institutes/robots.txt`
- `/api/v1/institutions`
- `/api/v1/institutions/nearby` with valid coordinates
- operator login and `/institutes/operations/production-signoff`

Then test invalid input, inactive records, authentication boundaries, search filters, and nearby-search limits.

## 4. Staging validation

Before production, deploy the same `main` commit to a Hostinger staging subdomain or staging directory. Use a separate database and separate credentials. Enable HTTPS and production-like PHP/session configuration.

Repeat the browser smoke test and then validate imports, evidence, provenance, monitoring, and public SEO behavior.

## 5. Production validation

Only after staging passes should the production deployment be performed. Follow the DEV-316 production validation runbook and DEV-318 security hardening requirements.

## Release rule

GitHub success alone does not prove that the live application works. Local testing proves application behavior during development; staging proves server integration; production checks prove the actual deployed environment.
