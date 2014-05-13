
install: vendor

vendor: composer.phar
	@php ./composer.phar install

composer.phar:
	@curl -sS https://getcomposer.org/installer | php

test: install
	@vendor/bin/phpunit --colors test/

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock \
		test/analytics.log

.PHONY: test
