# Git hooks

Versioned here rather than in `.git/hooks`, which git never tracks or clones.

## Install

Once per clone:

```bash
git config core.hooksPath .githooks
```

Check it took:

```bash
git config core.hooksPath   # → .githooks
```

## pre-push

Runs the full test suite and refuses the push if anything fails.

Bypass a single push with `git push --no-verify`.

Two things it works around, both local-only:

- **The suite is MySQL-only.** Some tests use MySQL's `FIELD()` and depend on
  constraint behaviour SQLite does not share, so running it on SQLite reports
  ~78 failures that are not real. The hook talks to `basketballv2_test` on the
  MySQL from `.env`.
- **PHP's 128M CLI default is too low.** The report-card PDF tests exceed it,
  so the hook runs with `-d memory_limit=1G`. GitHub's runner has no such cap.

If MySQL is unreachable the hook warns and lets the push through instead of
blocking. Gating on infrastructure that is down only teaches you to reach for
`--no-verify`, and CI still runs on every push either way.

Bring MySQL up:

```bash
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server start
```

Create the test database if it is missing:

```bash
mysql -uroot -e 'CREATE DATABASE IF NOT EXISTS basketballv2_test'
```
