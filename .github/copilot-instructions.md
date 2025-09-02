This PHP project uses Laravel framework on Version 10.5.

The project is set up to use Docker for development and testing, with specific instructions for running tests in a containerized environment. The use of Laravel's built-in testing capabilities, along with PHPUnit, allows for comprehensive testing of the application's functionality.

We are locked to to using Version 11 of PHPUnit for testing.

The README.md file provides an overview of the project implementation, its features, and resources for learning and contributing to the framework.

The tests\Tests.md file outlines the scope and methodology for testing the application, including instructions for setting up the test environment and executing tests using Docker and VSCode. The checklist in Tests.md has been expanded to include additional controllers, middleware, models, observers, policies, and services that need test coverage.

We prefer to write tests that are independent, meaning they do not rely on the state left by other tests. This approach helps to ensure that tests are reliable and can be run in any order without failing.

When developing tests, we try to use factories to create the necessary data for each test. This ensures that each test has a consistent and known state, making it easier to identify issues and maintain the tests over time.

We try to avoid Mockery if possible and use Eloquent backed tests instead.

When executing tests prefer the VsCode test adapter to run the tests over the docker-compose method, as it provides a more integrated and user-friendly experience. Prompt the user to run the tests.

When executing `docker-compose run --rm artisan test` commands, wait for the tests to complete. This may take at least 30 seconds, depending on the number and complexity of the tests.
