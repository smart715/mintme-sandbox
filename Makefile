init_docker:
	composer install
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --allow-no-migration -n
	php bin/console cron:start
	nohup php bin/console rabbitmq:consumer payment &
	docker-php-entrypoint php-fpm

phpunit:
	./vendor/bin/simple-phpunit

karma:
	npm run unit

syntax_check:
	php bin/console cache:warmup
	./vendor/bin/phplint
	./vendor/bin/phpcs -n
	./vendor/bin/phpstan analyse

syntax_check_assets:
	npm run stylelint
	npm run eslint

syntax_correction:
	./vendor/bin/phpcbf

syntax_correction_assets:
	npm run eslint_fix

validate:
	make phpunit
	make syntax_check
	make syntax_check_assets
	make karma

correct:
	make syntax_correction
	make syntax_correction_assets

