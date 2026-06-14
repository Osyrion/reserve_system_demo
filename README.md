# Developer Task – Barbershop Booking System

- [Solution Overview](#solution-overview--architecture-notes) — Explanation of the root cause and the implemented fix, notes on architecture and codebase
- [What to do](#what-to-do) — Main Task, Bonus Task, Submission
- [Project Overview](#project-overview) — Architecture, Getting Started, Using the App
- [Discussion Questions](#discussion-questions) — Interview only, not part of submission

## Solution Overview & Architecture Notes

> **Crucial Note:** Although the duplicate booking bug could be temporarily patched on the frontend, the **backend must always remain the single source of truth** for data integrity. The core of this fix is implemented on the backend to robustly prevent concurrent race conditions, while the frontend change serves purely as a UX defense layer.

### What Was Fixed

1. **Backend (Application Layer)**
    - Implemented a validation check (`hasOverlappingBooking`) inside `CreateBookingCommandHandler` to verify slot availability before persisting data.
    - Wrapped persistence in a `try-catch` to map `UniqueConstraintViolationException` to a clean `DomainException` instead of a 500 server error.

2. **Backend (Database Layer – Ultimate Guard)**
    - Added a `UNIQUE INDEX (stylist_id, start_time)` on `barbershop_bookings` via the Doctrine ORM mapping (`Booking.dcm.xml`), enforced at the schema level by `SchemaTool`.
    - This constraint acts as the last line of defense against concurrent requests that slip through the application layer during a race condition — independently of all application logic.

3. **Frontend (UX Layer)**
    - Added a synchronous double-submit guard using React `useRef` to prevent duplicate requests from rapid button clicks, safely executed *after* input validation to avoid permanently blocking the form on user errors.

4. **Automated Verification (Integration Tests)**
    - Fixed and extended the integration test suite for `CreateBookingCommandHandler` to ensure the slot overlap guard and DB-level unique constraint behave exactly as expected.
    - Full database isolation via a fresh SQLite instance created and destroyed for every test, guaranteeing clean runs with no shared state between tests. (See the **Integration Tests** section below for running instructions.)

## What to do

### Main Task

**Goal:** Find and fix the bug that causes duplicate bookings on the backend.

**Context:** Customers are reporting that after submitting a booking they sometimes receive two confirmations for the same time slot. In the administration panel we can see two identical bookings — same customer, same stylist, same time. It doesn't happen every time.

**Scope:**
- Investigate the root cause
- Propose and implement a fix on the backend
- We recommend using AI tools while working on this task

### Bonus Task

Create a Claude Code skill that solves a problem you actually hit while working on the main task. The skill must be functional and usable in the Claude Code CLI.

### Submission

Push your solution to a repository on any code hosting platform (GitHub, GitLab, Bitbucket, etc.) and share the link with us.

---

## Project Overview

A simple online booking application for two barbershops. Customers can choose a service, a stylist, a time slot, and submit a reservation.

### Architecture

- **Backend** – PHP application (Nette Framework, Doctrine ORM, GraphQL API), SQLite database
- **Frontend** – Next.js (React, Apollo Client, Tailwind CSS)
- Communication goes through a GraphQL endpoint on the backend

### Getting Started

#### Requirements

- Docker + Docker Compose

#### Steps

```bash
# 1. Start the containers (env files are created automatically)
make up

# 2. Run database migrations and load test data
make db-reset
```

The default configuration uses `docker-compose.local.yml`, which maps ports to localhost. Once running:

- **Frontend** at [http://localhost:3000](http://localhost:3000)
- **GraphQL API** at [http://localhost:8080/graphql](http://localhost:8080/graphql)

### Using the App

At [http://localhost:3000](http://localhost:3000) you'll find a list of available barbershops. Click on one to open its detail page, where you can:

1. Select a service (haircut, shave, …)
2. Choose a stylist and a date
3. Click an available time slot
4. Fill in your name and contact (email or phone) and submit with the **Book** button

### Booking Administration

The business panel is available at [http://localhost:3000/business-panel](http://localhost:3000/business-panel).

Credentials:

| Field    | Value    |
|----------|----------|
| Username | `admin`  |
| Password | `barber` |

After logging in you'll see all bookings grouped by date. You can switch between barbershops using the tabs at the top. For each booking in **Pending** status you can:

- **Confirm** – confirm the booking
- **Reject** – reject the booking

Confirmed and rejected bookings display their status and no further actions are available.

### Useful Commands

```bash
make up          # build and start containers
make down        # stop and remove containers
make db-reset    # run migrations and load fixtures
make fixtures    # load fixtures only (clears existing data)
make bash        # open a shell in the backend container
make logs        # tail backend container logs
```

---

## Discussion Questions

The following questions are **not part of the submission** — we'll go through them together during the interview. You are welcome and encouraged to bring your notes — on paper, phone, tablet, laptop, whatever works for you.

**General**

1. Did you notice anything unusual or non-standard in the project? Name at least three things and how you would approach them differently.

**Security & Architecture**

2. The GraphQL API has no authorization — how would you approach this and where would you implement it?
3. Walk me through a code review of the entire booking rejection flow — from the GraphQL mutation to `Booking::reject()`. What would you flag?
4. Businesses want to receive a webhook on every booking status change. How would you design this? Where would domain events fit in?

**Scalability & Reliability**

5. How would you design a system serving tens of thousands of businesses that together handle hundreds of thousands of requests per day?
6. This project runs as a single service. How would you approach logging if it were split into multiple microservices — how do you trace a single booking request across service boundaries?

**AI in Development**

7. How has AI changed your approach to software development over the last year? What do you find most useful about it, and what risks do you see?
