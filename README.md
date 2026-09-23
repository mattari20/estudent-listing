# eStudent.pk — Institutes & Education Directory

Permanent public path: `/institutes/`

## MVP technical foundation

- PHP 8.5
- Laravel 13
- MySQL 8.4 LTS
- Blade/server-rendered pages
- Progressive JavaScript
- Modular monolith

## First implementation slice

This slice establishes the Laravel application skeleton, /institutes/ routes, Institution model/migration, basic public views, frontend baseline and first feature test.

## Setup

Use the official Laravel 13 dependency installation flow. Then copy `.env.example` to `.env`, configure MySQL, generate the application key, run migrations, install/build frontend dependencies, and run tests.

Never commit `.env` or production secrets.
