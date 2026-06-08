# Agent Instructions

## Important Rules

 - All identified test failures, linting, static analysis errors and warnings MUST be fixed before committing
 - All tests must pass
 - 100% Code Coverage using phpunit/vitest
 - PHPStan/Larastan Level 8 for Static Analysis
 - PHP PSR12 Code Standards
 - Always follow Laravel Best practices
 - All UI changes must be validated using playwright
 - Clean up temporary files, screenshots in the project directory

## Tools

 - You have access to playwright CLI and headless browser for testing
 - The environment you're in has many tools, check ~/.claude/AGENTS.md for more details
 - You can open up a HTTPS URL forwarded to the workspace using the `cloudagent` CLI tool
 - You can open files and URLs for the user using the `cloudagent` CLI tool
 - You have access to local PHP, MySQL and Redis. Use those for a local development environment
