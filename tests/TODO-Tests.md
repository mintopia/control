# Testing

## Test Suites

- Unit: Contains stand-alone tests
- Feature: Contains tests that require a database connection and proper scaffolding

## Scope

- `app` Directory

## General

- General assumption: Code is good but may be missing some additions to properly test certain scenarios.
- Most tests are created using GitHub Copilot with GPT 4.1/GPT 4 Turbo or local Ollama models codellama or deepseek-r1
- Using general mocking or `Mockery` where needed

## Test Setup

- Added `.vscode` folder
    - `extensions.json` with recommmended extensions to build/test incl. `Lavarel Artisan` and `Lavarel Blade Snippets` and the VsCode PHP Test Explorer Docker
    - `launch.json` - currently empty/not used, was prepped for Xdebug
    - `settings.json` - currently empty, for required settings for this folder (deferred to workspace file)
    - `tasks.json` - adding task "Run Laravel Tests in Docker" which is TBD, currently not used.
- Added Lavarel configuraion in

### Environment discovery

- [-] Tests running locally - not desired (php not installed locally)
- [-] Tests running in VScode DevContainer - tried, works, but struggling with test adapter
- [x] Tests running in VsCode with Lavarel extension and auto-config (no settings needed) - Docker-compose is invoked through Lavarel extension

## Changes (so far)

- `docker-compose.yaml` > test > entrypoint extended to allow more than 128MB RAM
`entrypoint: [ "php", "-d", "memory_limit=512M", "vendor/bin/phpunit" ]`
- Added multiple entries in `database\factories` (required for tests)

## Tests

### Scope

- [x] Each file has _at least_ a stub file with the same name + `test.php` in the corresponding Folder Structure
- [x] Each file has a few unit tests created
- [ ] All Unit Tests succeed

### Roadblocks

- [ ] Some tests require a database connection - this is currently not available in the test envionment
- [ ] Many tests fail and require closer inspection

### Current pass rate:

316/457 - or 69%
