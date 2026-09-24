---

name: verify-change
description: >
Verify a completed implementation or bug fix before considering it done.
Use after code changes to inspect the diff, compare behavior with requirements,
run the exact verification commands from AGENTS.md, and identify regressions
or incomplete implementation.
-----------------------------

# Verify Change

Use this skill after implementing or modifying code.

Verification must be proportional to the scope of the change.

## 1. Inspect the Diff

Review all changed files.

Check for:

* unrelated modifications;
* debug or temporary code;
* generated files that should not be committed;
* hardcoded credentials or environment values;
* unnecessary dependency changes;
* duplicated business logic.

For lifecycle changes, specifically check that manual and automatic deletion still use the shared deletion workflow.

For notification changes, verify that required RabbitMQ publication has not been bypassed.

## 2. Check Requirements

For behavior changes, compare the implementation with the relevant sections of:

* `docs/requirements.md`

For changes affecting architecture or shared workflows, also check:

* `docs/architecture.md`

Do not reinterpret requirements to match the implementation.

## 3. Run Verification

Run the exact relevant commands defined in `AGENTS.md`.

Do not substitute different commands unless the documented command is unavailable or incorrect.

If a command cannot be run, report the reason.

## 4. Review Tests

Confirm that tests cover changed behavior rather than implementation details.

Pay particular attention when relevant to:

* upload validation;
* metadata persistence;
* storage consistency;
* manual deletion;
* automatic expiration;
* shared deletion behavior;
* RabbitMQ publication;
* failure paths.

Do not remove, skip, or weaken an existing test merely to make verification pass.

## 5. Report the Result

Summarize:

* verification commands executed;
* tests added or changed;
* relevant behavior checked;
* failures found and corrected;
* checks that could not be run and why.

Do not claim that verification succeeded if a required check was not executed or did not pass.
