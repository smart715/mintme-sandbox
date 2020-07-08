Mintme - cryptocurrency exchange market && token creator
========================================================

This is the main mintme project, representing a user panel, where each user can create his own token and start trading it or buy other user tokens.  
Each user have his own `trade` page, where he can create buy/sell orders for his own token. He can check all existing tokens via `trading` 
page and check trade page of each token. Also user have `wallet` page where he can get credentials for deposit BTC or MINTME or withdraw it, user also can check active orders, trading & deposit/withdraw history.
Panel also provides News page & Knowledge Base.

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

The following services are optional but are needed for some of features to work:

Service|Purpose
---|---
mail server|Sending emails: e.g. for completing the registration or changing user's email, and for Contact Us form requests
[rabbitmq](https://www.rabbitmq.com/download.html)|Communication between this project and deposit-gateway, widthderaw and token-contract
[webchaind](https://github.com/webchain-network/webchaind)|webchain demon for deposit-gateway and token-contract 
[token-contact](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/token-contract)|smart contracts for MintMe tokens
[viabtc](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/viabtc_exchange_server)| Fork of https://github.com/viabtc/viabtc_exchange_server. ViaBTC Exchange Server is a trading backend with high-speed performance, designed for cryptocurrency exchanges. Backend for trading internal user tokens.
[deposit-gateway](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/mintme-deposit-gateway)| Handles deposits of cryptocurrencies (MINTME coin and BTC) for the Mintme panel
[withdraw(coinimp-payment)](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/coinimp-payment)|Perform payouts and also for `app:wallet:balance` CLI command



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
```
php bin/console rabbitmq:consumer deploy &
```
to listen for token deploy
```
php bin/console rabbitmq:consumer contract_update &
```
to listen for updating mintDestination

Development
------------

## Docker

### Requirements

1. To run MintMe in Docker, you will need a Linux or Mac OS, it just doesn't work on Windows.
2. You will need 2 packages (follow the installation instructions for your distribution):
   - [Docker](https://docs.docker.com/engine/install/) 
   - [Docker Compose](https://docs.docker.com/compose/install/)
3. Time on your local machine MUST be synchronized with the global time. If you have a difference in even 30 seconds, webchain in Docker will not work.

### Installation

1. Add your SSH open key to GitLab
2. Clone MintMe panel repo from GitLab
3. Clone Git [submodules](https://www.atlassian.com/git/tutorials/git-submodule)
   ```shell script
   cd panel
   git submodule update --init --remote --recursive
   ```
4. Start Docker containers with docker-compose (do not close the terminal window) ```sudo docker-compose up```
5. Wait for the containers to build (only at the first run, usually about 40 minutes)
6. Wait for all the containers to start (usually about 5 minutes)
7. Wait for webchain to synchronize (usually about 1 hour at the first run, 5 minutes otherwise)

#### How to completely reinstall MintMe environment in Docker

1. Stop all running containers
   ```shell script
   cd panel
   sudo docker-compose stop
   ```
   or `Ctrl + c` in the docker-compose terminal window.
   
2. Remove all volumes
   ```shell script
   sudo docker volume prune
   ```

3. Remove all containers
   ```shell script
   sudo docker system prune -a
   ```
   
4. Remove the old dir with MintMe panel from your computer (or just rename it in case you have some not pushed commits you want to keep)
   ```shell script
   cd ..
   sudo rm -r panel
   ```

5. Follow the installation steps from above

### Usage:

#### Docker host or Docker container

You can run commands from either your [Docker host or Docker container](https://docs.docker.com/get-started/overview/#docker-architecture).

To run a command inside a Docker container, use [docker exec](https://docs.docker.com/engine/reference/commandline/exec/)

For example, to enter **panel** container terminal prompt `sudo docker exec -it panel_panel_1 /bin/bash`

Usually, you want to run your Git commands from Docker host and NPM, Composer and Symfony console commands - from Docker container.
 
This way you don't need to install NMP and Composer on your Docker host or deal with permissions problems caused by running them from the host.

#### Accessing running containers

##### Web interface

The panel web interface is available from your Docker host at https://localhost after the docker *panel* container starts completely. It usually takes about 5 minutes.

##### Database

You can access panel SQL database from your Docker host with `--protocol=tcp` parameter: `mysql -u root -p --protocol=tcp`. Default password is *root*.

Redis database can be accessed on port *16379* from Docker host with either [redic-cli](https://redis.io/topics/rediscli) or [Medis](https://github.com/luin/medis).

##### RabbitMQ

You can access RabbitMQ web interface from your Docker host on port *15672* http://localhost:15672/

##### Container logs

Use [docker logs](https://docs.docker.com/engine/reference/commandline/logs/) or [docker-compose logs](https://docs.docker.com/compose/reference/logs/) to access the logs output of a running container.

Some advanced logs are not directed to the standard output, but go to files instead. Use Docker [docker exec](https://docs.docker.com/engine/reference/commandline/exec/) to access the container file system.

To use `docker logs` or `docker exec`, you need to know the name or id of the container, you can get these by running either [docker ps](https://docs.docker.com/engine/reference/commandline/ps/) or [docke-compose ps](https://docs.docker.com/compose/reference/ps/).

The most useful logs and their location:

Log | Location | Command
--- | --- | ---
web panel | */var/www/html/panel/var/log* | `sudo docker exec -it panel_panel_1 /bin/bash`
via btc | */var/log/trade* | `sudo docker exec -it btc-service /bin/bash`
webchain | */root/.webchain/morden/log* | `sudo docker exec -it panel_webchain_1 /bin/bash`

To search for a text string in logs in terminal, use [grep](https://en.wikipedia.org/wiki/Grep). 

For example, to seach for text *error*, in case-insensitive way, in all text files in */var/log/trade* directory, run `grep -R -i "error" /var/log/trade`.

Contribution
------------
1. Take an issue from the [Redmine](https://redmine.abchosting.org/projects/mintme/issues);
2. On top of the current version's branch (e.g. `v1.0.1`) create branch named `issue-xxxx` where `xxxx` is the issue number (e.g. `issue-3483`);
3. Create a merge request for your branch to the current version's branch in [GitLab](https://gitlab.abchosting.org/abc-hosting/cryptocurrencies/mintme/merge_requests/new).

Read more about it in [this wiki article](https://redmine.abchosting.org/projects/mintme/wiki/Procedures_for_developers).

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
