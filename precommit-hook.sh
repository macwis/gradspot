#!/usr/bin/env bash

/usr/local/bin/php-cs-fixer fix ./src ./tests --config=.php_cs.dist --diff --verbose
/usr/local/bin/phpDocumentor -d -q ./src -t docs -po DocParser > /dev/null
./bin/phpunit --verbose --coverage-text --coverage-html reports/
