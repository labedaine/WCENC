#!/bin/bash

if [[ $USER != "postgres" ]]
then
    echo "Vous devez vous connecter sous le user postgres"
    echo "su - postgres"
    exit 1
fi

echo "Création instance"
sudo /u01/pgsql/pgdbca/bin/pgdbca.py -c -n pari -p 5432 -v 9.4 -d u03

sleep 2
echo "Connexion à l'instance pari"
source /u01/pgsql/pgbase/pgbase pari;

echo "Démarrage de l'instance pari"
sudo service pgsql_$PGINST start

sleep 2
echo "Création du role pari"
psql -c "CREATE ROLE pari LOGIN PASSWORD 'pari' SUPERUSER"

echo "Création de la database pari";
psql -c "CREATE DATABASE pari OWNER pari"

fichierHBA="/u03/pgsql/9.4/data/pari/pg_hba.conf"

if [[ $(grep "192.168.122" $fichierHBA | wc -l) == 0 ]]
then
    echo "Mise à jour du fichier hba_conf"
    echo "
local   pari            pari                                     md5
host    pari            pari            192.168.122.0/24         md5
" >> $fichierHBA
fi

echo "Redémarrage de l'instance pari"
sudo service pgsql_$PGINST restart

echo "Pour finaliser testez la connexion avec le user pari sur l'instance pari (mdp pari):"
echo "psql -U pari pari"
echo ""
echo "Depuis la VM 192.168.122.64:"
echo "psql -U pari -h 192.168.122.100 -p 5450 pari"

psql -U pari -h 192.168.122.100 -p 5450 pari < ./base.sql
psql -U pari -h 192.168.122.100 -p 5450 pari < ./populate.sql

