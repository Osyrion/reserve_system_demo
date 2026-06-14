# Reservio — Barbershop Booking Demo

Demo application for a barbershop booking system. Backend in PHP (Nette + Doctrine), frontend in Next.js, communication via GraphQL.

## Running the project

```bash
make up          # builds images, starts containers, installs composer deps
make db-reset    # runs migrations and loads fixtures
```

Application runs on:
- Frontend: http://localhost:3000
- GraphQL API: http://localhost:8080/graphql
- Admin panel: http://localhost:3000/business-panel (login: `admin` / `barber`)

Other commands:

```bash
make down        # stops containers
make bash        # shell in backend container
make logs        # backend logs
make fixtures    # reloads fixtures (clears existing data)
```

## Architecture

### Backend — DDD + CQRS

```
src/
├── Domain/          # entities, value objects, repository interfaces, exceptions
├── Application/     # command/query DTOs + handlers
├── Infrastructure/  # Doctrine repositories, bus adapters, fixtures
└── UserInterface/   # GraphQL types, mutations, queries, bootstrap
```

**New use-case is added in three steps:**
1. `Domain/` — repository interface (if new method is needed)
2. `Application/Command/` or `Application/Query/` — command/query DTO + handler
3. `UserInterface/GraphQL/Mutations/` or `.../Queries/` — GraphQL type + registration in `Bootstrap.php`

Commands are dispatched via command bus (`TacticianCommandBus`), not called directly.

### Database

- SQLite file: `backend/var/db.sqlite`
- Doctrine mapping: XML files in `backend/config/doctrine/*.dcm.xml` (not annotations)
- Migrations: `backend/migrations/`, run via Symfony Console

When DB schema changes, always:
1. Update `.dcm.xml` mapping
2. Create new migration (`php bin/console doctrine:migrations:generate`)
3. Run migration (`php bin/console doctrine:migrations:migrate`)

### GraphQL

- Library: `rebing/graphql-laravel` (PHP classes, not `.graphql` schema file)
- Type/mutation/query registration: `backend/src/UserInterface/GraphQL/Bootstrap.php`
- Response pattern: each mutation returns payload with `errors: [{field, message}]`
- List queries use connections (edges/nodes)

### Frontend

- Next.js 15, App Router (`src/app/`)
- Apollo Client (`src/lib/apollo-client.ts`)
- Queries and mutations are inline `gql` in components

## Important conventions

- **UUID** as primary key everywhere — `uuid` type via custom Doctrine type
- **`DateTimeImmutable`** for all timestamps
- **`BookingStatus` enum** — values: `Pending`, `Confirmed`, `Rejected`
- **Bookings are validated before save** — `hasOverlappingBooking()` in handler checks time slot collision; DB has unique index `(stylist_id, start_time)` as safety net
- **Errors propagate as `DomainException`** from domain/application layer — GraphQL layer catches and returns as `errors` in payload

## Tests

Project currently has no automated tests. When adding new features, consider adding at least an integration test for the command handler.
