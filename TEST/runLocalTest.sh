#!/bin/bash
#
txtred='\e[0;31m' # Red
txtgrn='\e[0;32m' # Green
txtrst='\e[0m'    # Text Reset
moveToCol40='\e[40G'

OK() {
    echo -e "$moveToCol40[ $txtgrn $1 $txtrst ]"
}

KO() {
    echo -e "$moveToCol40[ $txtred $1 $txtrst ]"
}

#
# Teste le retour d'une commande et affiche le résultat
#
function TesteRetour() {
    if [ $1 -ne 0 ] ; then
        KO KO
    else
        OK OK
    fi
}

export SINAPS_USE_POSTGRESQL=1

INSTALL_SQL="../install/sql/"

REINITBDD() {

    echo "*** Suppression/recreation des BDD ***"
    sudo -u postgres dropdb test_c;
    sudo -u postgres createdb test_c;
    sudo -u postgres dropdb test_r;
    sudo -u postgres createdb test_r;

    echo "*** Création des structures de donnée ***"
    sudo -u postgres psql -d test_c -qAt < $INSTALL_SQL/create_configuration_pg.sql
    sudo -u postgres psql -d test_r -qAt < $INSTALL_SQL/create_restitution_pg.sql

    echo "*** Droits sur les tables ***"
    for tbl in `sudo -u postgres psql -qAt -c "select tablename from pg_tables where schemaname = 'public';" test_c` ; do  sudo -u postgres psql -c "alter table \"$tbl\" owner to test" test_c >/dev/null; done
    for tbl in `sudo -u postgres psql -qAt -c "select tablename from pg_tables where schemaname = 'public';" test_r` ; do  sudo -u postgres psql -c "alter table \"$tbl\" owner to test" test_r >/dev/null; done
}

REINITBDD

rm -f /tmp/*.rerun

echo "----------------------------------------"
echo -n "PhpUnit:"
phpunit -c phpunit.local.xml --testsuite commun
TesteRetour $?

echo -n "Behat:"
./behat.sh --format failed --rerun /tmp/tests.rerun tests/ 
TesteRetour $?

echo "----------------------------------------"

