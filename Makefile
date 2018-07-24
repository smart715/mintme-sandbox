init_dev:
	composer install
	php bin/console doctrine:migrations:migrate --allow-no-migration -n

phpunit:
	./vendor/bin/phpunit
	./vendor/bin/phpunit --testsuite=integration

syntax_check:
	./vendor/bin/phplint
	./vendor/bin/phpcs -n
	./vendor/bin/phpstan analyse

syntax_correction:
	./vendor/bin/phpcbf