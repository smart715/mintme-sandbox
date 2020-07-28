phpunit:
	./vendor/bin/simple-phpunit --testsuite nothing && find tests/ -name "*Test.php" -and -not -path "*Controller/*" | ./vendor/bin/fastest "./vendor/bin/simple-phpunit -c phpunit.xml.dist {}"

phpfunctional:
	./vendor/bin/simple-phpunit --testsuite nothing && find tests/Controller -name "*Test.php" | ./vendor/bin/fastest "./vendor/bin/simple-phpunit -c phpunit.xml.dist {};"

phpunit-c:
	./vendor/bin/simple-phpunit --coverage-html ./coverage-php

jest:
	npm run unit --silent

syntax_check:
	php bin/console cache:warmup
	./vendor/bin/phplint
	./vendor/bin/phpcs -n
	./vendor/bin/phpstan analyse
	./vendor/bin/psalm --no-cache

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

