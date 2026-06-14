---
description: >
  Use when investigating bugs that could span multiple layers:
  duplicate records, missing data, unexpected behavior, or any bug
  where the root cause is unclear.
---

# Cross-Layer Bug Investigation

You are systematically investigating a bug across all layers of the stack.
Work through each step below. Skip a step only if it is clearly irrelevant.

## Live context

Focus only on files relevant to the suspected bug (booking / scheduling / mutations / handlers / repository / migrations).
Ignore unrelated changes.

## Investigation steps

### Step 0 — Expected behavior

Before debugging, define:

- Should this action ever create multiple records?
- What is the uniqueness rule?
- What is the expected invariant in the DB?

### Step 1 — Entry point

Find where the request enters the system.

- What is the user action that triggers the bug? (button click, form submit, API call)
- Locate the frontend component or page responsible for sending the request.
- Identify the exact GraphQL mutation or query being called, and the variables it sends.

### Step 2 — Trace the full flow

Follow the request through every layer. For each layer, read the actual code and
note what it does and what could go wrong.

| Layer | What to look for |
|---|---|
| **Frontend** | How and when is the request fired? Is it possible to fire it more than once (double-click, re-render, `Promise.all`, `useEffect` with bad deps)? |
| **GraphQL mutation / resolver** | Does it validate input? Does it call the command/query bus, or directly hit the DB? |
| **Application (command handler)** | Does the handler check preconditions before writing? Is there a guard against the reported scenario? |
| **Domain (entity / domain service)** | Are invariants enforced at the domain level? |
| **Repository / Infrastructure** | Does the repository implementation match the interface contract? Any missing filters (e.g., ignoring soft-deleted or status-filtered records)? |

### Step 3 — Check validations

- Is there input validation in the GraphQL input type?
- Is there a business rule check in the command handler before persisting?
- Are domain invariants enforced, or only at the application layer?

### Step 4 — Check race conditions

- Can two requests reach the handler simultaneously and both pass the pre-condition check before either commits?
- Is there a transaction or pessimistic lock around the critical section?
- Is optimistic locking (version field) an option if pessimistic lock is too expensive?
- Would the bug disappear under serial load but appear under concurrent requests?

### Step 5 — Check DB constraints

This is the most important safety net — it must exist independently of application logic.

- Does the database have a unique index or constraint that would catch duplicates as a last resort?
- What columns uniquely identify a record in the affected table? Is that enforced in the schema?
- Look at migration files and ORM mapping files for the relevant table.
- If the constraint is missing, add it first before fixing higher layers.

### Step 6 — Check frontend double-submit scenarios

- Is the submit button disabled while the request is in flight?
- Is the mutation called inside `Promise.all` or any construct that fires it multiple times?
- Does a navigation event, re-render, or `StrictMode` double-invoke the submit handler?

### Step 7 — Propose fix on all layers

For every root cause found, propose a concrete fix at the appropriate layer.
Structure your answer as:

**Root cause:** one sentence describing what actually goes wrong.

Then for each layer that needs a fix:

- **Layer name** — what to change and why. If the layer needs no change, say so explicitly so it is clear you checked it.

Fix order: DB constraint first (safety net, independent of all other logic), then domain/application guard, then frontend. 
Never rely solely on application-layer guards — a constraint violation at DB level is always the last line of defense.

### Step 8 — Verify with tests

- What integration or unit test would catch this bug if it regressed?
- Is there a DB-level test that inserts two conflicting records and asserts the constraint fires?
- Is there an application-layer test that verifies the guard rejects the second request before it reaches the DB?

---

Keep findings tight: one bullet per observation. Flag the root cause clearly before listing fixes.
