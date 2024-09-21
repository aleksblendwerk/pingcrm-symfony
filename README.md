# Ping CRM on Symfony
[![CI](https://github.com/aleksblendwerk/pingcrm-symfony/workflows/CI/badge.svg)](https://github.com/aleksblendwerk/pingcrm-symfony/actions)

A demo application to illustrate how [Inertia.js](https://inertiajs.com/) works, ported to Symfony from Laravel.

![Screenshot](screenshot.png)

Requires and is tested with PHP 8.3.

## Installation

Make sure you have the `symfony` binary ([Symfony CLI](https://symfony.com/download)) installed and in your `PATH`.

Clone the repo locally:

```sh
git clone https://github.com/aleksblendwerk/pingcrm-symfony.git pingcrm-symfony
cd pingcrm-symfony
```

Install dependencies:

```sh
composer install
npm ci
```

Build assets:

```sh
npm run dev
```

Start the local docker environment:

```sh
docker compose up
```

The current configuration uses MariaDB. If you're not using the supplied Docker environment, create a `.env.local` file
and adjust the `DATABASE_URL` accordingly.

Create the database, schema and load the initial data:

```sh
composer build-database
```

You're ready to go! Visit Ping CRM in your browser at http://localhost:8000/ and login with:

- **Username:** johndoe@example.com
- **Password:** secret

## Running tests

Keep in mind to adjust the `DATABASE_URL` in `.env.test` accordingly 
(or optionally create a `.env.test.local` file and put your overrides there).

Run the Ping CRM tests:

```
composer test
```

## Credits

- [Original Ping CRM](https://github.com/inertiajs/pingcrm) by Jonathan Reinink ([@reinink](https://github.com/reinink)) and contributors
- [Inertia.js server-side adapter for Symfony](https://github.com/SkipTheDragon/inertia-bundle) by Hannes Vermeire ([@rompetomp](https://github.com/rompetomp)), Tudorache Leonard-Valentin ([@SkipTheDragon](https://github.com/SkipTheDragon)) and contributors
- This port by Aleks Seltenreich ([@aleksblendwerk](https://github.com/aleksblendwerk))

Shout-outs to [all Ping CRMs all over the world](https://inertiajs.com/demo-application#third-party)!
