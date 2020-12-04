# Ping CRM on Symfony
[![CI](https://github.com/aleksblendwerk/pingcrm-symfony/workflows/CI/badge.svg)](https://github.com/aleksblendwerk/pingcrm-symfony/actions)

A demo application to illustrate how [Inertia.js](https://inertiajs.com/) works, ported to Symfony from Laravel.

![Screenshot](screenshot.png)

## About PHP 8.0

The current version on `main` most likely won't install properly on PHP 8.0 as some dependencies are not ready yet.
Please use PHP 7.4 in the meantime, I will try and add support for 8.0 as soon as possible.

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
yarn install
```

Build assets:

```sh
yarn build
```

The current configuration uses MySQL. Adjust the `DATABASE_URL` in `.env` accordingly 
(or optionally create a `.env.local` file and put your overrides there).

Create the database, schema and load the initial data:

```sh
composer build-database
```

Run the dev server:

```sh
symfony serve
```

You're ready to go! Visit Ping CRM in your browser, and login with:

- **Username:** johndoe@example.com
- **Password:** secret

## Running tests

Keep in mind to adjust the `DATABASE_URL` in `.env.test` accordingly 
(or optionally create a `.env.test.local` file and put your overrides there).

Run the Ping CRM tests:

```
composer test
```

## Remarks

One of the goals for this port was to leave the original JS side of things unchanged.
This promise has been kept, aside from one or two very minor changes.
As a result, the PHP backend code occasionally has to jump through a few hoops to mimic the expected response data 
formats which are partly catered to Laravel's out-of-the-box features.

Also, I am currently not really satisfied with the whole validation workflow, this might eventually get an overhaul.

Consider this a proof of concept, I am sure there is room for improvements. 
If any fellow Symfony developers want to join in to tackle things in more concise or elegant ways, let's go for it!

## Credits

- [Original Ping CRM](https://github.com/inertiajs/pingcrm) by Jonathan Reinink ([@reinink](https://github.com/reinink)) and contributors
- [Inertia.js server-side adapter for Symfony](https://github.com/rompetomp/inertia-bundle) by Hannes Vermeire ([@rompetomp](https://github.com/rompetomp)) and contributors
- This port by Aleks Seltenreich ([@aleksblendwerk](https://github.com/aleksblendwerk))

Shout-outs to [all Ping CRMs all over the world](https://inertiajs.com/demo-application#third-party)!
