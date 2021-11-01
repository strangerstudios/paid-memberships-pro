# How to set up your testing environment

## Prerequisites

* This plugin repo checked out on your local site into `/wp-content/plugins/this-plugin/` and other plugins you may need for the testing context.
* [Docker](https://www.docker.com/get-started)

## Step 1: Setting up your testing environment

[Follow the set up instructions](https://github.com/the-events-calendar/tric/blob/main/docs/setup.md) for [tric](https://github.com/the-events-calendar/tric).

## Step 2: Setting up your plugin for testing (if not already set up)

1. Run `tric here` from your local site's `wp-content/plugins/` folder (only needed once per session)
1. Run `tric use your-plugin-folder` to set which plugin target to use for testing (only needed once per session)
1. Run `tric init` to set up the initial configurations
1. Add your `.env`, `.env.example`, and `.env.testing.tric` files (these can just be copied/pasted from a working testing config)
1. Add your `codeception.dist.yml`, `codeception.example.yml`, and `codeception.tric.yml` files (these can just be copied/pasted from a working testing config)
1. Add a `tests/` folder into your plugin to store the tests
1. Add a `your-suite.suite.dist.yml` file like `wpunit.suite.dist.yml` (these can just be copied/pasted from a working testing config)
1. Add a folder for your suite like `tests/wpunit/` to store your tests for that suite
1. Add a `tests/_bootstrap.php` file as needed, this can help establish an autoloader for your `tests/_support/` classes if you have them
1. Add a `tests/your-suite/_bootstrap.php` file as needed, this can be helpful to run something like the DB table setup for PMPro that may be needed on each run
1. Add a class in `tests/your-suite/` based on the file organization you'd like to use like `tests/your-suite/functions/Levels/GetLevelTest.php`
1. That class should at minimal extend the `Codeception\TestCase\WPTestCase` class or your own test case class if you have one set up in `tests/_support/`
1. Add `test_my_function_returns_true_with_valid_id` style functions and include assertions like `$this->assertTrue( my_function( $valid_id ) )`

## Step 3: Running tests

1. Run `tric here` from your local site's `wp-content/plugins/` folder (only needed once per session)
1. Run `tric use your-plugin-folder` to set which plugin target to use for testing (only needed once per session)
1. Run `tric run your-suite` to run the codeception tests, add `-vvv` for debug/verbose output and you can use `codecept_debug( $some_string )` to see that debug info too
