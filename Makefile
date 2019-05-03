phpunit:
	./vendor/bin/simple-phpunit

phpunit-c:
	./vendor/bin/simple-phpunit --coverage-html ./coverage-php

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

