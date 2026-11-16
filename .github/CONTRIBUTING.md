# Contribute to Paid Memberships Pro

Paid Memberships Pro is the "community solution" for membership sites on WordPress, and we welcome contributions from the people who use it. This file holds the rules every pull request follows, whether a person or an AI agent wrote it. A bot checks each pull request against them and comments with anything missing. Pull requests from outside the Paid Memberships Pro team that still don't follow the rules 7 days later are closed.

__Please Note:__ GitHub is for bug reports and contributions only. If you have a support or customization question, go to our [Member Support Page](https://www.paidmembershipspro.com/support/) instead.

## Contents

- [Who can open a pull request](#who-can-open-a-pull-request)
- [Report the issue first](#report-the-issue-first)
- [Make the change](#make-the-change)
- [Commit messages say why, not what](#commit-messages-say-why-not-what)
- [Pull requests follow the template, top to bottom](#pull-requests-follow-the-template-top-to-bottom)
- [Changelog entries follow the house format](#changelog-entries-follow-the-house-format)
- [AI-assisted contributions](#ai-assisted-contributions)
- [The rules bot comments, then closes outside pull requests after 7 days](#the-rules-bot-comments-then-closes-outside-pull-requests-after-7-days)
- [What we don't accept](#what-we-dont-accept)
- [Code documentation](#code-documentation)
- [Additional resources](#additional-resources)

## Who can open a pull request

Open a pull request only when at least one of these is true:

* You use Paid Memberships Pro on a site and ran into the problem the change fixes.
* You maintain a plugin, gateway or service that integrates with Paid Memberships Pro, and the change concerns that integration.
* A Paid Memberships Pro team member approved that specific change before you opened the pull request. Link the approval.

If none applies, comment on the issue and ask first. Paid Memberships Pro team members can open pull requests for any issue.

## Report the issue first

* __Do not report potential security vulnerabilities here. Email them privately to [info@paidmembershipspro.com](mailto:info@paidmembershipspro.com) with the words "Security Vulnerability" in the subject.__
* Every pull request needs an issue. Search the [Issue Tracker](https://github.com/strangerstudios/paid-memberships-pro/issues) first, and [open one](https://github.com/strangerstudios/paid-memberships-pro/issues/new/choose) if none exists.
  * Clearly describe the issue including steps to reproduce the bug.
  * Fill in the earliest version that you know has the issue and the version of WordPress you're using.
* Check that no other open [pull request](https://github.com/strangerstudios/paid-memberships-pro/pulls) already covers the same issue.

## Make the change

* Fork the repository on GitHub.
* For bug fixes, branch from `dev`. For new features and enhancements, branch from the branch for the version the feature is milestoned for (such as `v4.0`).
* Pull in any upstream changes first.
	* `git remote add upstream https://github.com/strangerstudios/paid-memberships-pro.git`
	* `git checkout dev`
	* `git pull upstream dev`
	* `git push`
* Create a new branch for each separate bug fix or feature, so each pull request covers one issue only: `git checkout -b nameofmybugfixorfeature`.
* Follow the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) for the code you write (even though much of the existing PMPro code does not).
* Turn off any automatic beautifier in your IDE or dev environment. Unrelated style changes make a pull request harder to review. Ask the core development team if a file's inconsistent style is bothering you.
* Push the changes to your fork.
* For bug fixes, open the pull request against `dev`. For new features and enhancements, open it against the version the feature is milestoned for, usually the version after the current release.

## Commit messages say why, not what

The diff already shows what changed. A commit message carries what the diff cannot: the bug, the constraint or the decision behind the change.

| Good | Bad |
|---|---|
| Logins CSV export repeated every row because the loop never advanced `$i_start` | Update login-csv.php |
| Cap retries at 3 so a dead gateway cannot hold every checkout | Add retry limit |

* **Subject:** the reason in one line, under about 72 characters.
* **Body (optional):** the evidence, the alternative you rejected and why, and anything a future reader needs to avoid undoing the change.
* **Issue references:** use `Refs #123` in commits. `dev` is the default branch, so a closing keyword (`Fixes`, `Closes`, `Resolves`) in a commit closes the issue as soon as the commit lands there, even inside a sentence like "doesn't fix #123". Put `Fixes #123` in the pull request description instead.

## Pull requests follow the template, top to bottom

Opening a pull request on GitHub loads [`PULL_REQUEST_TEMPLATE.MD`](PULL_REQUEST_TEMPLATE.MD). `gh pr create` and the API do not, so copy the template into the body yourself. Fill in every section, in this order, and keep the headings:

1. **Summary:** one sentence on what the pull request does, above the first heading.
2. **Changelog entry:** one line in the [house format](#changelog-entries-follow-the-house-format).
3. **Commit message:** the message to use when the pull request is merged, written as described [above](#commit-messages-say-why-not-what).
4. **Why:** the bug, constraint or decision behind the change, and `Fixes #123`.
5. **How to test:** numbered steps a reviewer can follow on a WordPress site.
6. **Testing done:** what you ran and what happened. For a bug fix, show that the change fixes the reported behavior: the reproduction before and after, or a test that fails without the fix. Say plainly what you did not test.
7. **Risk:** anything that could break for existing sites, such as a changed filter, a database change or a different default.
8. **AI assistance:** check the box and name every model or tool that generated code or text.
9. **Your connection to this change:** which of the [eligibility conditions](#who-can-open-a-pull-request) applies. Team members can leave it blank.
10. **Checklist:** tick only what you actually verified, and say why next to any item that doesn't apply.

Describe why the change is needed. Do not list the diff file by file; name a file only when the diff doesn't make its role obvious.

## Changelog entries follow the house format

The changelog entry is what site owners read when deciding whether an update matters to them. Write it for them, not for the reviewer. Each entry starts with one label, is a complete sentence a non-developer can read (two sentences at most), and ends with the pull request or issue number and the contributors' GitHub usernames.

| Label | Use it when |
|---|---|
| `SECURITY` | Something was broken as related to security (for example escaping) and you fixed it |
| `FEATURE` | New functionality |
| `ENHANCEMENT` | You made an existing feature a little better |
| `BUG FIX/ENHANCEMENT` | It was broken in obscure cases and you made it a little better |
| `BUG FIX` | It was broken and you fixed it |
| `REFACTOR` | You rewrote it but it works exactly the same as before |
| `DEPRECATED` | A function has been deprecated |

* Spell out acronyms and abbreviations. Exceptions: PMPro, REST API, WP, product names, and anything that references code.
* Wrap function names, class names and hooks in backticks.
* Call out any new filter or action, and any change to a default.
* If nothing in the pull request is user-facing (docs, tests, CI), write `None:` and the reason.

Example:

```
BUG FIX: Fixed the Logins report CSV export writing every user once per batch on sites with more users than the batch size. #3945 (@github_username)
```

## AI-assisted contributions

AI-assisted contributions are welcome and held to the same standard as any other.

* **A human is responsible for every pull request.** The person submitting reviews the complete diff, tests the change, and authorizes that specific pull request before an agent opens it.
* **Disclose it.** Check the box in the template's "AI assistance" section and name every model or tool that generated code or text.
* **No autonomous contribution agents.** Don't give an agent standing permission to pick issues and open pull requests, and don't ask one to scan the repository for things to fix.
* **Sign agent-written review comments.** A review or reply an agent wrote, posted from a person's account, ends with `~ 🤖 Agent name (model)`. Comments from an identifiable bot account don't need the signature.
* **Agents read [`AGENTS.md`](../AGENTS.md)**, which lists these rules in short form. `CLAUDE.md` loads it for Claude Code.

## The rules bot comments, then closes outside pull requests after 7 days

The [Pull request rules](workflows/pr-rules.yml) workflow checks each pull request when it opens, when the description is edited, and when commits are pushed. Drafts are checked when they're marked ready for review. It looks for:

* every template section, in order, filled in
* a changelog entry that starts with a label or `None:`
* an issue linked in "Why"
* no closing keyword in the proposed commit message or in any commit
* no commit subject that only names a file ("Update login-csv.php")
* a ticked eligibility box and a ticked "I have read CONTRIBUTING.md" box
* a base branch of `dev` or a version branch

When something is missing, it posts one comment listing what to fix, adds the `needs: pr rules` label and fails the check. Fix the description or push a commit and it checks again, updating the same comment.

**Pull requests from outside the team** that still fail 7 days after the last update are closed with a comment. Fix the items and reopen it, or open a new pull request.

**Pull requests from the Paid Memberships Pro team** get the same comment and label but are never closed by the bot. The team is anyone who is a member of the `strangerstudios` organization or has write access to this repository.

The bot checks form, not substance. A team member still reviews whether the change is right, and can close a pull request that passes the check but doesn't meet these rules.

## What we don't accept

* Pull requests from people with no [connection to the change](#who-can-open-a-pull-request).
* Drive-by refactors, bulk lint or formatting cleanups, and "while I'm here" edits in unrelated code.
* Speculative changes that don't fix a reported problem.
* Pull requests opened by an agent working on its own.
* Pull requests that come with an offer of paid work. Pull requests are contributions, not sales pitches.

## Code documentation

* Document every function, filter, class and class method with phpDoc.
* An example of [how PMPro uses phpDoc blocks can be found here](https://gist.github.com/sunnyratilal/5308969).
* When adding or editing a method in a class, include `@access {private|public|protected}`.
* Use tabs, not spaces. The tab indent size is 4 for all Paid Memberships Pro code.

## Additional resources

* [General GitHub Documentation](https://help.github.com/)
* [GitHub Pull Request documentation](https://help.github.com/send-pull-requests/)
