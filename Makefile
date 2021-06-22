THREADS_COUNT ?= 0

ifneq ($(THREADS_COUNT),0)
	THREADS_ARG ?= --threads=$(THREADS_COUNT)
endif

phpunit:
	./vendor/bin/simple-phpunit --testsuite nothing && find tests/ -name "*Test.php" -and -not -path "*Controller/*" | ./vendor/bin/fastest -p$(THREADS_COUNT) "./vendor/bin/simple-phpunit -c phpunit.xml.dist {}"

phpfunctional:
	./vendor/bin/simple-phpunit --testsuite nothing && find tests/Controller/ -name "*Test.php" | ./vendor/bin/fastest -p$(THREADS_COUNT) "./vendor/bin/simple-phpunit -c phpunit.xml.dist {}"

phpunit-c:
	./vendor/bin/simple-phpunit --coverage-html ./coverage-php

jest:
	npm run unit

syntax_check:
	php bin/console cache:warmup
	./vendor/bin/phplint
	./vendor/bin/phpcs -n
	./vendor/bin/phpstan analyse
	./vendor/bin/psalm --no-cache $(THREADS_ARG)

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
	make jest

correct:
	make syntax_correction
	make syntax_correction_assets

all:
	make correct
	make validate

generate_translations:
	php bin/console app:load-translations-ui

