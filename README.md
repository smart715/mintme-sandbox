Mintme - cryptocurrency exchange market && token creator
========================================================

This is the main mintme project, representing a user panel, where each user can create his own token and start trading it or buy other user tokens. User can create posts, chat with another users, deploy his token on blockchain. Also, this project allows users to generate api keys and user our api, which allows user to create orders, withdraw funds, get trading history etc .
Each user have his own `trade` page, where he can create buy/sell orders for his own token. He can check all existing tokens via `trading` 
page and check trade page of each token. Also user have `wallet` page where he can get credentials for deposit BTC or MINTME or withdraw it, user also can check active orders, trading & deposit/withdraw history.
Panel also provides News page & Knowledge Base.

Backend written on php, symfony framework. Frontend written on js, vuejs framework. Panel communicates with another our services via http json rpc requests and rabbitmq  

It fetches and updates user tokens data via 
[viabtc](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/viabtc_exchange_server). 
Also it communicates with 
[gateway](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/mintme-gateway) 
gateways to perform blockchain operaions.

The panel has configurable taker/maker fees and default token quantity parameters. You can also change cryptocurrency withdraw fee in `crypto` table.

# Production installation

## Requirements

This section is for production installation only, if you want to install for development, go to [Development installation](#development-installation).

* [php](https://secure.php.net/downloads.php) 7.2.2+ with following extensions (mysqli, pdo, pdo_mysql, zip, bcmath, pcntl, sockets, gd, xml, gmp) and composer
* a webserver (you can use [symfony's](https://packagist.org/packages/symfony/web-server-bundle) from dev composer dependencies)
* [nodejs](https://nodejs.org/) 10+, npm 7.5+
* [mysql](https://www.mysql.com/downloads/) 5.6 or compatible DBMS

The MintMe panel web interface can start without the next services, but they are required for the full functional:

| Service                                                                                                     | Purpose                                                                                                                                                                                                             |
|-------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| mail server                                                                                                 | Sending emails: e.g. for completing the registration or changing user's email, and for Contact Us form requests                                                        
| [viabtc](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/viabtc_exchange_server)          | Fork of https://github.com/viabtc/viabtc_exchange_server. ViaBTC Exchange Server is a trading backend with high-speed performance, designed for cryptocurrency exchanges. Backend for trading internal user tokens. 
| [gateway](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/mintme-gateway)| Handles any blockchain operation like Smart Contract methods for tokens, deposits, widthdrawals.|

## Installation

This section is for production installation only, if you want to install for development, go to [Development installation](#development-installation).

1. Make sure the required services are up and running.
2. Clone this repository and checkout needed branch.
3. Create a dedicated clean MySQL (or compatible) database.
4. Install required dependencies: `composer install --no-dev` for staging/production environment or `composer install` for development environment.
5. Configure the settings in `config/parameters.yaml` file which will be filled in with default values at the previous step.
6. Clear Symfony cache to apply your changes in `parameters.yaml` file by running `php bin/console cache:clear`.
6. Configure your web server to pass all the requests to the `public/index.php` file.
7. Build frontend: `npm install` and `npm run prod` for production environment or `npm run dev` for development environment.
8. Check if everything's up by visiting your page on the web browser, and you are done!

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
```
php bin/console rabbitmq:consumer deploy &
```
to listen for token deploy
```
php bin/console rabbitmq:consumer contract_update &
```
to listen for token contract updates (mint destination and releases)
```
php bin/console rabbitmq:consumer email &
```
to listen for email notifications

# Development installation

Read about how to install MintMe for local development in Docker in [Procedures for developers - Docker](https://redmine.abchosting.org/projects/mintme/wiki/Procedures_for_developers#Docker).

# Developing with local docker mintme-gateway

In order to sync a local docker mintme-gateway and docker panel (for gateway development and hot-reload),
you need to stop your panel's mintme-gateway container and start the external one:

```bash
# Stop your panel's mintme-gateway
docker-compose stop mintme-gateway

# Go to mintme-gateway project directory (git clone it)
# Install dependencies locally
make install
# Run mintme-gateway container
make run-standalone
```

Keep in mind that the external docker gateway will depend on panel's services (db, rabbitmq)

This configuration will connect your docker panel and docker gateway, and you will be able develop in mintme-gateway
while testing with panel

# Local mailer dispatching

We use [mailhog](https://github.com/mailhog/MailHog) to catch all outcoming emails. Open [http://localhost:8025](http://localhost:8025) to see message content.

# Contribution

1. Take an issue from the [Redmine](https://redmine.abchosting.org/projects/mintme/issues);
2. On top of the current version's branch (e.g. `v1.0.1`) create branch named `issue-xxxx` where `xxxx` is the issue number (e.g. `issue-3483`);
3. Create a merge request for your branch to the current version's branch in [GitLab](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/merge_requests/new).

Read more about the development process in [Procedures for developers](https://redmine.abchosting.org/projects/mintme/wiki/Procedures_for_developers).

# Makefile commands

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
