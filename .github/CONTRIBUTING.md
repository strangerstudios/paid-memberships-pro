# Contribute to Paid Memberships Pro

Paid Memberships Pro is the "community solution" for membership sites on WordPress, so contributions of all kinds are appreciated. This file holds the rules every pull request must follow. **A pull request that does not follow them is closed without review**, whether a person or an AI agent wrote it. You are welcome to open a new one that does.

__Please Note:__ GitHub is for bug reports and contributions only. If you have a support or customization question, go to our [Member Support Page](https://www.paidmembershipspro.com/support/) instead.

## Contents

- [Report the issue first](#report-the-issue-first)
- [Make the change](#make-the-change)
- [Commit messages say why, not what](#commit-messages-say-why-not-what)
- [Pull requests follow the template, top to bottom](#pull-requests-follow-the-template-top-to-bottom)
- [Changelog entries follow the house format](#changelog-entries-follow-the-house-format)
- [A pull request is closed without review when](#a-pull-request-is-closed-without-review-when)
- [Code documentation](#code-documentation)
- [Additional resources](#additional-resources)

## Report the issue first

* __Do not report potential security vulnerabilities here. Email them privately to [info@paidmembershipspro.com](mailto:info@paidmembershipspro.com) with the words "Security Vulnerability" in the subject.__
* Every pull request needs an issue. Search the [Issue Tracker](https://github.com/strangerstudios/paid-memberships-pro/issues) first, and [open one](https://github.com/strangerstudios/paid-memberships-pro/issues/new/choose) if none exists.
  * Clearly describe the issue including steps to reproduce the bug.
  * Fill in the earliest version that you know has the issue and the version of WordPress you're using.
* Check that no other open [pull request](https://github.com/strangerstudios/paid-memberships-pro/pulls) already covers the same issue.

## Make the change

* Fork the repository on GitHub.
* For bug fixes, branch from `dev`. For new features and enhancements, branch from the branch for the version the feature is milestoned for.
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
* **Issue references:** use `Refs #123` in commits. A closing keyword (`Fixes`, `Closes`, `Resolves`) in a commit closes the issue when the commit lands on the default branch, even inside a sentence like "doesn't fix #123". Put `Fixes #123` in the pull request description instead.

## Pull requests follow the template, top to bottom

Opening a pull request loads [`PULL_REQUEST_TEMPLATE.MD`](PULL_REQUEST_TEMPLATE.MD). Fill in every section in this order and keep the headings:

1. **Summary:** one sentence on what the pull request does.
2. **Changelog entry:** one line in the [house format](#changelog-entries-follow-the-house-format).
3. **Commit message:** the message to use when the pull request is merged, written as described [above](#commit-messages-say-why-not-what).
4. **Why:** the bug, constraint or decision behind the change, and `Fixes #123`.
5. **How to test:** numbered steps a reviewer can follow on a WordPress site.
6. **Testing done:** what you ran and what happened. For a bug fix, show that the change fixes the reported behavior: the reproduction before and after, or a test that fails without the fix. Say plainly what you did not test.
7. **Risk:** anything that could break for existing sites, such as a changed filter, a database change or a different default.
8. **AI assistance:** check the box and name every model or tool that generated code or text.
9. **Checklist:** tick only what you actually verified, and say why next to any item that doesn't apply.

The GitHub website loads the template for you; `gh pr create` and the API do not, so copy the template into the body yourself. Describe why the change is needed. Do not list the diff file by file; name a file only when the diff doesn't make its role obvious.

### AI-assisted pull requests

AI-assisted contributions are welcome and held to the same standard as any other. The person submitting is responsible for the change: they review the complete diff, test it, and authorize that specific pull request. Agents that pick their own work and open pull requests on their own are not accepted. [`AGENTS.md`](../AGENTS.md) holds the rules for agents.

## Changelog entries follow the house format

Each entry starts with one label, is a complete sentence a non-developer can read (two sentences at most), and ends with the pull request or issue number and the contributors' GitHub usernames.

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
* Call out any new filter or action.

Example:

```
BUG FIX: Fixed the Logins report CSV export writing every user once per batch on sites with more users than the batch size. #3945 (@github_username)
```

## A pull request is closed without review when

* It has no linked issue.
* It skips or reorders sections of the pull request template, or leaves them empty, including the AI assistance section.
* The changelog entry is missing or doesn't follow the house format.
* A commit message only says what changed, or uses a closing keyword.
* It covers more than one issue, or includes unrelated formatting changes.
* It targets a branch other than `dev` (bug fixes) or the milestoned version branch (features).
* It comes with an offer of paid work. Pull requests are contributions, not sales pitches.

## Code documentation

* Document every function, filter, class and class method with phpDoc.
* An example of [how PMPro uses phpDoc blocks can be found here](https://gist.github.com/sunnyratilal/5308969).
* When adding or editing a method in a class, include `@access {private|public|protected}`.
* Use tabs, not spaces. The tab indent size is 4 for all Paid Memberships Pro code.

## Additional resources

* [General GitHub Documentation](https://help.github.com/)
* [GitHub Pull Request documentation](https://help.github.com/send-pull-requests/)
