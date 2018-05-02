<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";
require_once __DIR__.'/TestModel.php';

class SequenceTest extends PHPUnit_Framework_TestCase {

    private $dbh = NULL;

    public function setUp() {
        App::initialise();
        Utils::initFakeServeur();
        $server = getenv("DB_SERVER");
        
        $this->dbh = new PDO (   "pgsql:host=".$server.";dbname=test_r",
                                "test",
                                "test", 
                                 array( PDO::ATTR_PERSISTENT => true));
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->tearDown();

        $this->dbh->query('
            CREATE TABLE "Test" (
                    "id" SERIAL,
                    "nom" VARCHAR(255) NOT NULL ,
                    "description" VARCHAR(8192) NULL DEFAULT NULL,
                    "date" TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY ("id"))');

//        $query->updateSequence();
    }

    public function tearDown() {
        $this->dbh->query( "DROP TABLE IF EXISTS \"Test\"");
    }

    public function testSequence() {
        $this->dbh->query(" 
            INSERT INTO \"Test\" VALUES( 1, 'test1', NULL, NULL)");
        $this->dbh->query(" 
            INSERT INTO \"Test\" VALUES( 2, 'test2', 'Commentaire 1', NULL)");
        
        $this->assertEquals(Test::count(), 2);

        $test = new Test();
        $test->nom = "sequence";
        $test->save();
        
        $retourTest = Test::where('nom', "sequence")->first();
        $this->assertNotNull($retourTest, "L'objet \$test n'a pas été sauvegardé");
        $this->assertEquals($retourTest->nom, "sequence");
        $this->assertEquals($retourTest->id, 3);
    }
}
