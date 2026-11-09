# Instructions for AI coding agents

This file applies to any AI agent (Claude Code, Codex, Copilot, Cursor and others) that writes code, commits or pull requests for this repository. The full rules are in [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md); read it completely before opening a pull request. **Pull requests that break these rules are closed without review.**

## Before opening a pull request

1. Read [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md) and [`.github/PULL_REQUEST_TEMPLATE.MD`](.github/PULL_REQUEST_TEMPLATE.MD).
2. Confirm an issue exists for the change and that no open pull request already covers it.
3. Branch from `dev` for bug fixes, or from the milestoned version branch for features, and open the pull request against that same branch.

## Hard rules

- **One issue per pull request.** No drive-by refactors, no reformatting of lines you didn't need to change, no beautifier output.
- **Commit messages say why, not what.** The subject is the reason in one line under about 72 characters. Use `Refs #123` in commits; never `Fixes`, `Closes` or `Resolves`.
- **Fill in the pull request template in order:** summary sentence, changelog entry, commit message, why (with `Fixes #123`), how to test, testing done, risk, checklist. Don't delete or reorder sections.
- **Changelog entry in the house format:** `LABEL: Complete sentence. #PR (@username)`, with a label from the table in `CONTRIBUTING.md`. Backtick hooks, functions and classes.
- **Don't restate the diff** file by file in the description. Explain the reason and the evidence.
- **Say what you did not test.** Never claim a test or result you didn't run.
- **New code** follows the WordPress Coding Standards, has phpDoc, and uses tabs.
- **Security issues** are never filed or fixed in public; they're emailed to info@paidmembershipspro.com.
