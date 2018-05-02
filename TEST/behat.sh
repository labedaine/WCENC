#!/bin/sh

# On cré l'autoload qui va bien
#cd tools/behat/
#php composer.phar dump-autoload
#cd - >/dev/null

SUITE=$1

tools/behat/bin/behat \
--config test_commun/behat.yml \
--lang=fr \
--suite $1 \
-v \
--colors \
--format pretty $*
