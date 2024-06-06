<?php
namespace Deployer;

require 'recipe/symfony.php';
require 'recipe/composer.php';

// Project name
set('application', 'mintme_panel');
//timeout for commands
set('default_timeout', 600);
// Project repository
set('repository', 'ssh://git@gitlab.abchosting.org:2279/abc-hosting/cryptocurrencies/mintme/panel.git');
//prevent to clone submodules
set('git_recursive', false);

set('branch', function() {
    $branch = 'master';

    if (input()->hasOption('branch') && !empty(input()->getOption('branch'))) {
        $branch = input()->getOption('branch');
    }

    return $branch;
});

// [Optional] Allocate tty for git clone. Default value is false.
//set('git_tty', true);

set('writable_mode', 'chown');
set('http_user', "mintme:www-data");


set('bin/console', '{{release_path}}/bin/console');

// Shared files/dirs between deploys
add('shared_files', [
    'config/parameters.yaml',
    '.env',
//    'maintenance_on'
]);

add('shared_dirs', [
    'var/log',
    'public/uploads',
    'uploads_coin'
]);

// Writable dirs by web server
add('writable_dirs', [
    'var/log',
    'var/cache',
    'uploads_coin',
    'public/uploads'
]);
set('allow_anonymous_stats', false);

// Hosts

localhost()
    ->set('deploy_path', '/home/mintme/panel_deployer');

// Tasks

task('node-install', function () {
    run('cd {{release_path}} && npm install -d');
});

task('node-build', function () {
    run('cd {{release_path}} && npm run prod');
});

task('composer-install', function() {
     run('cd {{release_path}} && export $(grep -v "^#" .env | xargs) && composer install --no-dev --optimize-autoloader');
});

task('cache-clear', function() {
     run('cd {{release_path}} && export $(grep -v "^#" .env | xargs) && bin/console cache:clear --no-debug --no-warmup');
});

task('cache-warmup', function() {
     run('cd {{release_path}} && export $(grep -v "^#" .env | xargs) && bin/console cache:warmup');
});

task('database-migrate', function() {
     run('cd {{release_path}} && export $(grep -v "^#" .env | xargs) && bin/console doctrine:migrations:migrate --env=prod --no-interaction');
});

task('load-translations', function() {
     run('cd {{release_path}} && export $(grep -v "^#" .env | xargs) && bin/console app:load-translations-ui');
});

task('cache-move', function() {
     run('cd {{release_path}} && mv var/cache/prod var/cache/prod.todelete');
});

task('cache-delete', function() {
     run('cd {{release_path}} && rm -rf var/cache/prod.todelete');
});

task('opcache-reset', function() {
     run('/usr/local/bin/cachetool.phar opcache:reset --fcgi=/dev/shm/php-mintme74.sock --tmp-dir=/tmp');
});

task('php-fpm-reload', function() {
     run('sudo /etc/init.d/php7.4-fpm reload');
});

task('db-backup', function() {
     run('cd {{deploy_path}}/shared && /usr/bin/mysqldump -h `cat .env |grep DATABASE_URL|cut -d ":" -f 3 |cut -d "@" -f 2` -u`cat .env |grep DATABASE_URL|cut -d ":" -f 2|tr -d "/"` -p`cat .env |grep DATABASE_URL|cut -d ":" -f 3 |cut -d "@" -f 1` mintme_frontend --routines > /home/mintme/backup/mintme-panel-database-`date +"%m_%d_%Y-%H_%M_%S"`.sql');
});

task('change-version', function() {
     run('/usr/local/bin/change-version-mintme.sh');
});

task('rocket-notify', function() {
     run('/usr/local/bin/rocket-notify-mintme.sh "Mintme was updated from branch {{branch}} on `hostname`" "#mintme"');
});

task('restart-consumers', function() {
     run('sudo /bin/systemctl restart mintme-deposit-gateway-consumer;
        sudo /bin/systemctl restart mintme-market-gateway-consumer;
        sudo /bin/systemctl restart mintme-token-contract-gateway-consumer;
        sudo /bin/systemctl restart mintme-token-contract-update-consumer;
        sudo /bin/systemctl restart mintme-withdraw-gateway-consumer;
        sudo /bin/systemctl restart mintme-email-consumer;'
     );
});

task('check-mintme-user', function() {
     run('/usr/local/bin/check-mintme-user.sh');
});

task('maintenance-on', function() {
     run('touch {{deploy_path}}/current/maintenance_on && touch {{release_path}}/maintenance_on');
});

task('maintenance-off', function() {
     run('rm -f {{deploy_path}}/current/maintenance_on && rm -f {{release_path}}/maintenance_on');
});

task('downgrade-db', function() {
    run('./deployer_scripts/deployer-downgrade-db.sh {{branch}} {{deploy_path}}/current');
});

task('deploy', [
    'check-mintme-user',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'db-backup',
    'downgrade-db',
    'deploy:update_code',
    'deploy:shared',
    'opcache-reset',
    'composer-install',
    'node-install',
    'node-build',
    'load-translations',
    'deploy:writable',
    'database-migrate',
    'change-version',
    'cache-warmup',
    'cache-clear',
    'php-fpm-reload',
//second clearing cache
    'cache-move',
    'cache-warmup',
    'cache-clear',
    'opcache-reset',
    'php-fpm-reload',
    'cache-delete',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'rocket-notify',
]);

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Restart consumers after symlinking to the new version
after('deploy:symlink', 'restart-consumers');

// Migrate database before symlink new release.

//before('deploy:symlink', '');

//before('deploy:failed', '');
