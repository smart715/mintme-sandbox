init_dev:
	composer install
	php bin/console doctrine:migrations:migrate --allow-no-migration -n
	npm install

phpunit:
	./vendor/bin/simple-phpunit

karma:
	npm run unit

syntax_check:
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
