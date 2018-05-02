----------
- README -
----------

*** Organisation des répertoires tests ***
tests 
    basededonnee                =====> 1 par composant de SINAPS
    collecteur
    commun                      =====> bibliothèques communes
    configuration
    hypervision
    restitution
    phpunit.xml                 =====> runner de l'ensemble des tests php de SINAPS (phpunit et behat)

"composant"
    ihm                         =====> javascript
    services                    =====> webservices PHP
    scripts                     =====> scripts en batch
    tools                       =====> scripts pouvant être lancés par l'utilisateur

commun
    js
        "lib_sinaps"            =====> une lib développée pour SINAPS
    php
        "lib_sinaps"

    
Tests PHP:    
    features                    =====> Tests Behat
    phpunit/Behat               =====> Tests runner pour Behat
    phpunit/xxxx                =====> Tests phpunit

*** Execution des tests ***
voir INSTALL.txt

--------------
- Historique -
--------------
| 1 | djacques | initialisation du document