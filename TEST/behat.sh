#!/bin/sh

# On cré l'autoload qui va bien
#cd tools/behat/
#php composer.phar dump-autoload
#cd - >/dev/null

tools/behat/bin/behat \
--config test_commun/behat.yml \
--lang=fr \
--suite tests \
-v \
--colors \
--format pretty $*
