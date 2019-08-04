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

* [php](https://secure.php.net/downloads.php) 7.2.2+ with following extensions (mysqli, pdo, pdo_mysql, zip, bcmath, pcntl, sockets, gd, xml) and composer
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

You also need to add this to crontab jobs 
```
* * * * * /path/to/symfony/install/app/console cron:run 1>> /dev/null 2>&1
```
to launch cron operations to update users lock-in.

Additional commands to execute:
```
php bin/console rabbitmq:consumer payment &
``` 
to listen for failed payouts to pay amount quantity back to user and
```
php bin/console rabbitmq:consumer deposit &
```
to listen for incoming deposits
```
php bin/console rabbitmq:consumer market &
```
to listen for new data in markets

Development
-----------

### Requirements:

* [Docker](https://docs.docker.com/install/#backporting) 
* [Docker Compose](https://docs.docker.com/compose/install/#install-compose)

### Installation:

1. Clone this repository and checkout needed branch;
2. Pull submodule repositories with `git submodule update --init --recursive`;

You may need to replace `localhost` DNS with your docker-machine 
ip address in case of using `docker-toolbox`.  
Also you should check your config files in nested projects. It should contains references to 
services running by itself. \
If you'd like to change deposit/withdraw address you need optionaly change .docker configs. 

### Example:

```yaml
database_host: http://db:3306 # We are replacing actual ip with a service alias `db`
```

### Usage:

1. Run `docker-compose up -d` to setup a services cluster.  
2. Wait untill all services aren't started
3. Check panel with `localhost` or docker-machine ip

### PS:

I really don't recommend to use it on Windows. I warned you ;)


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
  
