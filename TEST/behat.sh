#!/bin/sh

# On cré l'autoload qui va bien
#cd tools/behat/
#php composer.phar dump-autoload
#cd - >/dev/null
echo "BEHAT"
tools/behat/bin/behat \
--config test_commun/behat.yml \
--suite=tests \
--lang=fr \
--colors \
--format pretty $*

echo ""
echo "PHPUNIT"
tools/behat/bin/phpunit tests/phpunit/
