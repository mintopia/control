# Control

## Introduction

LAN Party user accounts, ticket management and seat picker. It is designed to work with multiple social authentication providers and ticket providers.

### Social Provider Support

 - Discord
 - Steam (No authentication, just account linking)
 - Twitch

These are using Laravel Socialite, so any provider supported by Socialite can be integrated.

### Ticket Provider Support

 - Ticket Tailor
 - WooCommerce
 - Internal

These are custom integrations but more can be added and used if people develop them. The Internal provider allows you to manually issue tickets to users.


## Technology

This project is written in PHP 8.4 using the Laravel 12 framework. It was migrated from Laravel 10 and 11 so has some
legacy project structure - but this is the intended upgrade path.

Horizon and Telescope are installed and enabled, with access limited to the admin role. The application itself is
served using Laravel Octane and FrankenPHP.

Websocket communications are handled using Laravel Reverb.

## Development Setup

You will need to create a Discord application and have the Client ID and Client Secret available.

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

Add the redirect URLs from the `control:setup-discord` step to your Discord OAuth2 configuration.

You should now be able to login. The first user will be given the admin role.

## Production Deployment

In the `example` directory there is a docker compose file and some .env example files. These are for the setup I use.
Just rename the .env files and edit them accordingly. You can get a [random Laravel application key here](https://generate-random.org/laravel-key-generator).

You need to expose the `control` container to the public. This is configured to listen on port 80
in the docker compose, so you probably want something like Traefik or Caddy in-front as a reverse proxy.

I'm running this with an external docker network called `frontend` with Caddy running as HTTP/HTTPS ingress. You will
need to add a network section for the `control` service to add it to the `frontend` network if you
want to do this.

You will need to make a logs directory and chmod it 777 as I still need to sort permissions out.

To bring up the site, run the following:

```bash
docker compose up -d redis database
docker compose run --rm artisan migrate
docker compose run --rm artisan db:seed
docker compose run --rm artisan setup:discord
docker compose up -d
```

You should now be able to visit the site and login. From here you can use the admin menu to configure the site.

## Observability

Control supports basic observability functionality in using an OpenTelemetry collector. It can support traces, logs
and metrics. If enabled, it will create traces for all HTTP requests. To enable it, add the following to your `.env`:

```dotenv
OPENTELEMETRY_ENABLED=true
```

For logging output, a logger is defined and can be used. I suggest you use this with your usual logger, eg. `daily`.
You can specify this logging with the following environment variables:

```dotenv
LOG_CHANNEL=stack
LOG_STACK=opentelemetry,daily
```

By default it is configured to send to an OpenTelemetry container running with the name `collector`. An example config
is supplied with placeholders for sending data to [Honeycomb](https://www.honeycomb.io/).

The plan will be to add further spans within individual requests and have spans for the jobs and queued actions.

## Contributing

It's an open source project and I'm happy to accept pull requests. I am terrible at UI and UX, which is why this is entirely using server-side rendering. If someone wants to use Vue/Laravel Livewire - please go ahead!

## Roadmap

The following features are on the roadmap:

 - Better UI/UX. I'm currently using [tabler.io](https://tabler.io) and entirely server-side rendering.
 - Full-featured API. There's a basic one to support seating plan refreshes. I need to refactor it and improve it.
 - UI Customisation from Admin Pages. Currently the UI colours, branding is all either in the `.env` or compiled into the CSS at build.
 - Unit Tests. This was very rapidly developed, I'm sorry!
 - PHPCS and PHPStan. Should be aiming for PSR-12 and level 8 PHPStan.

## Thanks

This would not exist without the support of the following:

- UK LAN Techs

## License

The MIT License (MIT)

Copyright (c) 2023 Jessica Smith

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
