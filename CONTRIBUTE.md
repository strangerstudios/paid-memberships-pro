#Contribute to Paid Memberships Pro

Paid Memberships Pro is the "community solution" for membership sites on WordPress, and so contributions of all kinds are appreciated.

When contributing, please follow these guidelines to ensure things work as smoothly as possible.

__Please Note:__ GitHub is for bug reports and contributions only. If you have a support or customization question, go to our [Member Support Page](http://www.paidmembershipspro.com/support/) instead.

## Getting Started

* __Do not report potential security vulnerabilities here. Email them privately to [info@paidmembershipspro.com](mailto:info@paidmembershipspro.com) with the words "Security Vulnerability" in the subject.__
* Submit a ticket for your issue, assuming one does not already exist.
  * Raise it on our [Issue Tracker](https://github.com/strangerstudios/paid-memberships-pro//issues)
  * Clearly describe the issue including steps to reproduce the bug.
  * Make sure you fill in the earliest version that you know has the issue as well as the version of WordPress you're using.

## Making Changes

* Fork the repository on GitHub
* For bug fixes, checkout the DEV branch of the PMPro repository.
* For new features and enhancements, checkout the branch for the version the feature is milestoned for.
* Make the changes to your forked repository
  * Make sure to pull in any "upstream" changes first.
  	* Use `git remote add upstream https://github.com/strangerstudios/paid-memberships-pro.git` to set the upstream repo
	* Use `git checkout upstream/dev` then `git pull` to pull in the latest updates on dev.
	* Use `git checkout dev` then `git merge upstream/dev` to merge those updates into your dev.
  * Ensure you stick to the [WordPress Coding Standards](https://codex.wordpress.org/WordPress_Coding_Standards) (even though much of the PMPro code does not currently)
* When committing, reference your issue (if present) and include a note about the fix
* Push the changes to your fork 
* For bug fixes, submit a pull request to the latest versioned branch of the PMPro repository (e.g. 1.9).
* For new features and enhancements, submit a pull request to the DEV branch of the PMPro repository. 
* We will process all pull requests and make suggestions or changes as soon as possible. Feel free to ping us politely via email or social networks to take a look at your pulls.

## Code Documentation

* We would like for every function, filter, class, and class method to be documented using phpDoc standards.
* An example of [how PMPro uses phpDoc blocks can be found here](https://gist.github.com/sunnyratilal/5308969).
* Please make sure that every function is documented so that when we update our API Documentation things don't go awry!
	* If you're adding/editing a function in a class, make sure to add `@access {private|public|protected}`
* Finally, please use tabs and not spaces. The tab indent size should be 4 for all Paid Memberships Pro code.

# Additional Resources
* [General GitHub Documentation](https://help.github.com/)
* [GitHub Pull Request documentation](https://help.github.com/send-pull-requests/)
