<?php
/**
 * Simule serveur HTTP & BDD Restitution.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

require_once __DIR__."/../../WEB/ressource/php/services/SystemService.php";

class FakeServer {

    static public function init() {
        FakeInput::init();
        FakeConfigReaderService::init();
        FakeMailService::init();

        SinapsApp::singleton(
            "dbConnection", function () {
                $dbConnectionString = FakeServer::getDbConnectionString();

                $dbh = new ReconnectPDO(
                    $dbConnectionString,
                    "test",
                    "test",
                    array(
                        PDO::ATTR_PERSISTENT => TRUE
                    )
                );
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                return $dbh;
            }
        );

    // On simulera ici les données de configuration
        SinapsApp::$config = array (
            //"toto" => "tata"
        );

        return SinapsApp::make("dbConnection");
    }

    static public function truncateAll() {

        // On réinstalle la base
        $chemin = "/usr/bin/php ".__DIR__."/../../WEB/tool/populate_db.php";
        //exec("$chemin --recreate --test", $output);
    }

    static public function getDbConnectionString() {
        if(!getenv("DB_SERVER")) {
            putenv("DB_SERVER=192.168.122.100");
        }

        $dbConnectionString = "pgsql:host=" .  getenv("DB_SERVER") . ";dbname=test";
//print ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> $dbConnectionString\n";
        return $dbConnectionString;
    }
}
