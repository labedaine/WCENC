<?php
/**
 * Simule serveur HTTP & BDD Restitution.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

class FakeServer {
    static public function init($databaseType) {
        FakeInput::init();
        FakeConfigReaderService::init();
        FakeMailService::init();

        SinapsApp::singleton(
            "dbConnection", function () use ($databaseType) {
                $dbConnectionString = FakeServer::getDbConnectionString($databaseType, "test");

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

    static public function truncateAll($tables=NULL) {
        if ($tables === NULL)
            $tables = static::$tables;

        $dbh = App::make("dbConnection");

        $prefix = "";
        $cmdResetSeqId = NULL;

        $cmd = "DELETE FROM ";

        foreach($tables as $table) {
            $tableNoQuote = $table;
            $table = "\"$table\"";
            $dbh->query($cmd. $table . $prefix);

            // On remet les ids à zéro
            $cmdResetSeqId = "SELECT setval(pg_get_serial_sequence('".$tableNoQuote."', 'id'), 1) FROM $table;";
            $cmdResetSeqId = "ALTER SEQUENCE \"".$tableNoQuote."_id_seq\" RESTART WITH 1;";
            $dbh->query($cmdResetSeqId);
        }
    }

    static public function truncateSession() {
        $dbh = App::make("dbConnection");

        $prefix = "";
        $cmdResetSeqId = NULL;

        $cmd = "DELETE FROM ";

        $tableNoQuote = "session";
        $table = "session";
        $dbh->query($cmd. $table . $prefix);

        // On remet les ids à zéro
        $cmdResetSeqId = "SELECT setval(pg_get_serial_sequence('".$tableNoQuote."', 'id'), 1) FROM $table;";
        $cmdResetSeqId = "ALTER SEQUENCE \"".$tableNoQuote."_id_seq\" RESTART WITH 1;";
        $dbh->query($cmdResetSeqId);

    }

    static public function getDbConnectionString($databaseType, $nomDb) {
        if(!getenv("DB_SERVER")) {
            putenv("DB_SERVER=192.168.122.100");
        }

        $dbConnectionString = "pgsql:host=" .  getenv("DB_SERVER") . ";dbname=$nomDb";
//print ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> $dbConnectionString\n";
        return $dbConnectionString;
    }

    static $tables = array(
        "equipe","etat_match","phase","stade","match","session","utilisateur"
        );

}
