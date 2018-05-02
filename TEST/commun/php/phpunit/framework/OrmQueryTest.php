<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";
require_once __DIR__.'/TestModel.php';

class OrmQueryTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $server = getenv("DB_SERVER");
        
        $this->dbh = new PDO (   "pgsql:host=".$server.";dbname=test_r",
                                "test",
                                "test", 
                                 array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                                        PDO::ATTR_PERSISTENT => true));
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->dbh->query('
            CREATE TABLE "Test" (
                    "id" SERIAL,
                    "nom" VARCHAR(255) NOT NULL ,
                    "description" VARCHAR(8192) NULL DEFAULT NULL,
                    "date" TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY ("id"))');

        $this->dbh->query(" 
            INSERT INTO \"Test\" VALUES( 1, 'test1', NULL, NULL)");
        $this->dbh->query(" 
            INSERT INTO \"Test\" VALUES( 2, 'test2', 'Commentaire 1', NULL)");
         
        // On actualise l'id de séquence   
		$query = new OrmQuery("Test", null, $this->dbh);
        $query->updateSequence();
    }

    public function tearDown() {
        $this->dbh->query( "DROP TABLE \"Test\"");
    }

    public function testFirstSuccess() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $result = $query->first();

        $this->assertNotNull($result);
        $this->assertEquals( "Test", get_class($result));
    }


    public function testGetSuccess() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $result = $query->get();

        $this->assertEquals(2, count($result));
        foreach($result as $testObj) {
            $this->assertEquals("Test", get_class($testObj));
        }
    }

    public function testWhereNumericOk() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $result = $query->where( "id", ">", "1")->get();

        $this->assertEquals(1, count($result));
        foreach($result as $testObj) {
            $this->assertEquals("Test", get_class($testObj));
        }

        $this->assertEquals("test2", $testObj->nom);
    }

    public function testWhereStringOk() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $result = $query->where( "nom", "=", "test2")->get();

        $this->assertEquals(1, count($result));
        foreach($result as $testObj) {
            $this->assertEquals("Test", get_class($testObj));
        }

        $this->assertEquals("test2", $testObj->nom);
    }

    public function testWhereValideEtRetourne0() {

    }

    public function testWhereSurColonneInexistante() {

    }

    public function testChaineDeWhere() {

    }

    public function testRawWhere() {

    }

    public function testChaineDeRawWhere() {

    }

    public function testTake() {

    }

    // **********************************************
    // ***** INSERTS
    // **********************************************
    public function testInsert1ElementOk() {
		
        $nom = "testInsert1";
        
        $query = new OrmQuery("Test", null, $this->dbh);

        $query->insert(array("nom" => $nom));

        $controle = $query->where("nom", "=", $nom)->first();

        $this->assertNotNull($controle);
        $this->assertEquals( $nom, $controle->nom);
    }

    public function testInsertGetId1ElementOk() {
        $nom = "testInsert1";
        
        $query = new OrmQuery("Test", null, $this->dbh);

        $id = $query->insertGetId(array("nom" => $nom));

        $controle = $query->where("id", "=", $id)->first();

        $this->assertNotNull($controle);
        $this->assertEquals( $nom, $controle->nom);
    }

    public function testInsert3ElementsOk() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $id = $query->insertGetId(array(
            array( "nom" => "testInsert1"),
            array( "nom" => "testInsert2", "description" => "description1"),
            array( "nom" => "testInsert3", "description" => "description2")));

        $query = new OrmQuery("Test", null, $this->dbh);
        $controle = $query->where("nom", "like", "testInsert%")->get();

        $this->assertNotEmpty($controle);
        $this->assertEquals( 3, count($controle));
        $this->assertEquals( "testInsert1", $controle[0]->nom);
        $this->assertEquals( "description1", $controle[1]->description);
        $this->assertEquals( "description2", $controle[2]->description);
    }


    /************************************
     TEST UPDATES 
     ************************************/
    public function testUpdateAvecWhere() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $query  ->where('id', 1)
                ->update(array('description' => 'une description'));

        $query = new OrmQuery("Test", null, $this->dbh);
        $controle = $query->where("id",1)->first();

        $this->assertEquals( 'une description', $controle->description);
    }

    public function testUpdateAvecWhereN2() {
        $query = new OrmQuery("Test", null, $this->dbh);

        $query  ->where('id', 1)
                ->update(array('description' => 'une autre description', 'nom' => 'un autre nom'));

        $query = new OrmQuery("Test", null, $this->dbh);
        $controle = $query->where("id",1)->first();

        $this->assertEquals( 'une autre description', $controle->description);
        $this->assertEquals( 'un autre nom', $controle->nom);
    }
}
