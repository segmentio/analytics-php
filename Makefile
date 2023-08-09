bootstrap:
	.buildscript/bootstrap.sh

dependencies: vendor

vendor: composer.phar
	@php ./composer.phar install

composer.phar:
	@curl -sS https://getcomposer.org/installer | php

test: lint
	@vendor/bin/phpunit --colors test/
	@php ./composer.phar validate

lint: dependencies
	@if php -r 'exit(version_compare(PHP_VERSION, "5.5", ">=") ? 0 : 1);'; \
	then \
		php ./composer.phar require overtrue/phplint --dev; \
		php ./composer.phar require squizlabs/php_codesniffer --dev; \
		php ./composer.phar require dealerdirect/phpcodesniffer-composer-installer --dev; \
		
		./vendor/bin/phplint; \
		./vendor/bin/phpcs; \
	else \
		printf "Please update PHP version to 5.5 or above for code formatting."; \
	fi

release:
	@printf "releasing ${VERSION}..."
	@printf '<?php\nglobal $$SEGMENT_VERSION;\n$$SEGMENT_VERSION = "%b";\n' ${VERSION} > ./lib/Version.php
	@git changelog -t ${VERSION}
	@git release ${VERSION}

clean:
	rm -rf \
		composer.phar \
		vendor \
		composer.lock

.PHONY: bootstrap release clean
