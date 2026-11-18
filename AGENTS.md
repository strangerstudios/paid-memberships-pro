# Instructions for AI coding agents

This file applies to any AI agent (Claude Code, Codex, Copilot, Cursor and others) that writes code, commits or pull requests for this repository. The full rules are in [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md); read it completely before opening a pull request. A bot checks every pull request against those rules and comments with anything missing. Pull requests from outside the Paid Memberships Pro team that still fail 7 days later are closed.

## Before opening a pull request

1. Read [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md) and [`.github/PULL_REQUEST_TEMPLATE.MD`](.github/PULL_REQUEST_TEMPLATE.MD).
2. Confirm the human you work for has authorized this specific pull request, and that they meet one of the conditions in *Who can open a pull request* (or are on the Paid Memberships Pro team).
3. Confirm an issue exists for the change and that no open pull request already covers it. Work from that issue; don't scan the repository for things to fix.
4. Branch from `dev` for bug fixes, or from the milestoned version branch (such as `v4.0`) for features, and open the pull request against that same branch.
5. `gh pr create` and the GitHub API do not load the template. Copy `.github/PULL_REQUEST_TEMPLATE.MD` into a body file, fill in every section, and pass it with `--body-file`.

## Hard rules

- **One issue per pull request.** No drive-by refactors, no reformatting of lines you didn't need to change, no beautifier output.
- **Commit messages say why, not what.** The subject is the reason in one line under about 72 characters, never just "Update file.php". Use `Refs #123` in commits; never `Fixes`, `Closes` or `Resolves`, because `dev` is the default branch and they close the issue on push.
- **Fill in the template in order and keep its headings:** summary sentence, Changelog entry, Commit message, Why (with `Fixes #123`), How to test, Testing done, Risk, AI assistance, Your connection to this change, Checklist.
- **Changelog entry in the house format:** `LABEL: Complete sentence. #PR (@username)`, with a label from the table in `CONTRIBUTING.md`, written for site owners. Backtick hooks, functions and classes. Use `None: <reason>` when nothing is user-facing.
- **Don't restate the diff** file by file in the description. Explain the reason and the evidence.
- **Show the fix changes the reported behavior:** the reproduction before and after, or a test that fails without the fix. Say what you did not test, and never claim a test or result you didn't run.
- **Tick a checklist box only for what you verified.** If an item doesn't apply, say why next to it.
- **Disclose AI assistance** in the template's AI section and name every model or tool used.
- **Sign review comments you write** from a person's account with `~ 🤖 Agent name (model)`.
- **When the rules bot comments,** fix every item it lists by editing the description or pushing a commit. Don't argue with it in comments.
- **New code** follows the WordPress Coding Standards, has phpDoc, and uses tabs.
- **Security issues** are never filed or fixed in public; they're emailed to info@paidmembershipspro.com.
