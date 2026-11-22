# CLAUDE.md

## Project

Repcoach is an MCP server that coaches workouts: saved programs, sessions run
set by set, history, and prompts for an AI client. It is built with Symfony 8.1
for the Symfony AI & MCP Hackathon.

## Work tracking

Everything starts from a ticket (GitHub issue), tracked in the project
https://github.com/orgs/Guiziweb/projects/15

Three levels, linked as sub-issues:

- an epic (label `epic`) is a whole area of the server
- a story (label `story`) groups the tasks of one deliverable
- a task (label `task`) is one commit and one pull request

A ticket has a title and no description: the title says the result. The
details live in the pull request.

## Commands

Always use the Makefile, never the underlying tools directly.

```
make install   composer install
make browsers  install Chromium for the browser tests (needs Node.js 20 or later)
make migrate   run the database migrations
make import    load the exercise catalogue from RepDB
make fixtures  load the sample programs and their history (after make import)
make cs        check code style
make cs-fix    fix code style
make lint      Symfony checks: YAML syntax, container wiring, Doctrine mapping
make stan      PHPStan, level max
make test      PHPUnit
make ci        cs + lint + stan + test, what CI runs (browser tests need make browsers)
make serve     start the web app on http://127.0.0.1:8010
```

## Commits

Conventional Commits, written in English. One line per commit, no body.
The scope is the domain: shared, exercise, workout, session, server, web,
fixtures, docs or ci.

Commits are created by the GitHub App `guiziwebbot` through the API, so they
show as Verified, never under a personal account.

## Branches and pull requests

One branch per task, named `<issue number>-<short slug>`
(`35-create-the-symfony-application`), and one pull request per task. A task
that needs the previous one is stacked on it: its pull request has the
previous branch as base, and it is retargeted to `main` once that one is
merged. Squash and merge, in order, with the one-line commit message.

Pull requests are opened by the same bot, with the owner as reviewer. The
title is plain text, not a Conventional Commit. The body starts with
`Closes #N`, then what changed, what was checked locally and what was not.

## When something unplanned comes up

Do not fold it into the pull request in progress. Create a task for it under
the story it belongs to (a new story if none fits), with a title that says
the result, and handle it like any other ticket: its own branch, its own
pull request.

If it blocks the task in progress, say so in that pull request, do the new
task first, and stack the rest on it.

If it is only a doubt that does not block anything, write it as a comment on
the pull request instead of a ticket.

## Code

No comments. The code explains itself through naming.

Every file starts with `declare(strict_types=1)`.

A comment or a docblock is allowed only for a reason the code cannot show
(a library quirk, a type PHPStan needs).

## Tests

Every change ships with the tests that cover it.

Tests never use the network: HTTP is mocked.

## CI

Every push and every pull request runs `make ci`. It must pass.
