
install: vendor

vendor: composer.phar
	@php ./composer.phar install

composer.phar:
	@curl -sS https://getcomposer.org/installer | php

test: install
	@vendor/bin/phpunit --colors test/
	@php ./composer.phar validate

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock

.PHONY: test
