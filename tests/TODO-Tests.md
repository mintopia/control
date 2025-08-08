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

Tests running in VsCode with [PHP Unit Test Explorer Lavarel](https://marketplace.visualstudio.com/items?itemName=recca0120.vscode-phpunit) extension and the following config

#### Workspace setting

These settings are located in personal workspace due to different local folder structure

```json
{
    // Laravel Extra Intellisense: this may not work for all features, but allows some PHP code execution via Docker
    // This may not be needed if PHP is installed locally
    // "LaravelExtraIntellisense.phpCommand": "docker compose run --rm php -r \"{code}\"",

    // Enable running artisan commands in Docker
    "artisan.docker.enabled": true,
    "artisan.docker.command": "docker compose run --rm artisan",

    // PHPUnit Text Explorer (recca0120.vscode-phpunit) - Configuration for CONTROL
    "phpunit.command": "docker compose run --rm artisan test",
    "phpunit.php": "", // this needs to be empty (default is php)
    "phpunit.paths": { // working on Windows and translating paths to docker/linux relative paths
        "d:\\Code\\Public\\control\\tests": "tests",
        "d:\\Code\\Public\\control\\app": "app"
    },
}
```


#### Test Environment creation

Copied from README, adapted as needed

```bash
cp .env.example .env
docker compose up -d redis db
docker compose run --rm composer install
docker compose run --rm artisan key:generate
docker compose run --rm artisan migrate
docker compose run --rm artisan db:seed
docker compose run --rm artisan control:setup-discord
docker compose run --rm npm install
docker compose run --rm npm run build
docker compose up -d
```



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
