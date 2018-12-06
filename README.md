Mintme - cryptocurrency exchange market && token creator
========================================================

This is the main mintme project, representing a user panel. 
It fetches and updates user tokens data via 
[viabtc](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/viabtc_exchange_server). 
Also it communicates with 
[withdraw](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/coinimp-payment) 
and 
[deposit](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/mintme-deposit-gateway) 
gateways to perform money operaions.

The panel has configurable taker/maker fees and default token quantity parameters. You can also change cryptocurrency withdraw fee in `crypto` table.

Requirements:
-------------

* [php](https://secure.php.net/downloads.php) 7.2.2+ with following extensions (mysqli, pdo, pdo_mysql, zip, bcmath, pcntl, sockets, gd) and composer
* a webserver (you can use [symfony's](https://packagist.org/packages/symfony/web-server-bundle) from dev composer dependencies)
* [nodejs](https://nodejs.org/) 8+, npm 5.6+
* [mysql](https://www.mysql.com/downloads/) 5.6 or compatible DBMS

Installation
------------
1. Make sure the required services are up and running;
2. Clone this repository and checkout needed branch;
3. Create a dedicated clean MySQL (or compatible) database;
4. Install required dependencies: `composer install` for development environment or `composer install --no-dev` for staging/production environment;
5. [Configure](docs/Configuration.md) the settings in `config/parameters.yaml` file which will be created on the previous step;
6. Configure [your](https://symfony.com/doc/current/setup/web_server_configuration.html#content_wrapper) webserver to pass all requests to the `public/app.php` file.
 In dev environment you can just run [Symfony's](https://symfony.com/doc/current/setup/built_in_web_server.html) webserver via `server:start` console command;
7. Build frontend: `npm install` and `npm run dev` (or `npm run prod` in staging/production). Re-run `npm run dev` after making changes to frontend to reflect them in your local webserver;
8. Check if everything's up by visiting your page in the web browser, and you are done!

You also need to run `php bin/console cron:start` to lounch cron operations to update users lock-in and
`php bin/console rabbitmq:consumer payment &` to listen for failed payouts to return amount quantity.

Contribution
------------
1. Take an issue from the [Redmine](https://redmine.abchosting.org/projects/mintme/issues);
2. On top of the current version's branch (e.g. `v1.0.1`) create branch named `issue-xxxx` where `xxxx` is the issue number (e.g. `issue-3483`);
3. Create a merge request for your branch to the current version's branch in [GitLab](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/merge_requests/new).

Makefile commands
----------

Command|Purpose
---|---
phpunit                   |run php unit tests
karma                     |run assets unit tests
syntax_check              |run php linting and syntax validation
syntax_check_assets       |run js linting and syntax validation
validate                  |run all commands above
syntax_correction         |perform corrections for phpcs linting rules
syntax_correction_assets  |perform corrections for js linting rules
syntax_correction_assets  |perform corrections for js linting rules
correct                   |run all correction commands above
  
