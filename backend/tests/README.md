# Integration Tests

This directory contains integration tests for the barbershop booking system.

## Running Tests

```bash
# Install dev dependencies (including PHPUnit)
composer install

# Run all integration tests
./vendor/bin/phpunit

# Run a specific test file
./vendor/bin/phpunit tests/Integration/Barbershop/CreateBookingCommandHandlerTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Test Structure

- `IntegrationTestTrait` — shared trait providing Nette DI container setup, SQLite schema creation, and fixture loading
- `Barbershop/CreateBookingCommandHandlerTest` — tests for the `CreateBookingCommandHandler`: successful booking, duplicate slot guard, DB-level unique constraint, and UUID result

## Database Isolation

Each test gets a **fresh SQLite database**. In `setUp`, the previous database file is deleted, a new schema is created via `SchemaTool`, and fixtures are loaded. In `tearDown`, the connection is closed and the file is deleted again.

Tests do **not** use transactions or rollbacks — isolation is achieved by recreating the database from scratch for every test.

The database file is stored at `var/test.sqlite` and is never committed to version control.

## Fixtures

Fixtures are loaded from `App\Infrastructure\Fixture\BarbershopFixtures` and seed two businesses with stylists and services. The fixture UUIDs are stable and referenced directly in tests as constants.
