<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";

require_once __DIR__.'/TestModel.php';
require_once __DIR__.'/TestModel2.php';

class SinapsModelTest extends PHPUnit_Framework_TestCase {
    static public $dbh;

    public function setUp() {
        $server = getenv("DB_SERVER");
        self::$dbh = new PDO (  "pgsql:host=".$server.";dbname=test_r",
                                "test",
                                "test", 
                                 array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                                        PDO::ATTR_PERSISTENT => true));
        self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::$dbh->query(' 
            CREATE TABLE "Test" (
                    "id" SERIAL,
                    "nom" VARCHAR(255) NOT NULL ,
                    "description" VARCHAR(8192) NULL DEFAULT NULL,
                    "date" TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY ("id"))');

        self::$dbh->query(" 
            INSERT INTO \"Test\" VALUES( 1, 'test1', NULL, NULL)");
        self::$dbh->query(" 
            INSERT INTO \"Test\" VALUES( 2, 'test2', 'Commentaire 1', NULL)");

        App::initialise();
        App::singleton( 'dbConnection', function() {
            return SinapsModelTest::$dbh;
        });
        
        // On actualise l'id de séquence   
		$query = new OrmQuery("Test", null, self::$dbh);
        $query->updateSequence();

    }

    public function tearDown() {
        self::$dbh->query( "DROP TABLE \"Test\"");
    }

    public function testFindOk() {
        $result = Test::find(2);

        $this->assertEquals( "Test", get_class($result));
        $this->assertEquals( "test2", $result->nom); 
    }

/* @TODO: améliorer le dirty 
    public function testDirty() {
        $result = Test::find(2);

        $this->assertEquals( "Test", get_class($result));
        $this->assertEquals( 0, count($result->getDirty())); 
    }*/

    public function testFindMissing() {
        $result = Test::find(67);

        $this->assertNull($result);
    }

    public function testAllAvecNomDeTableDifferentDeNomDeClasse() {
        $result = Test2::all();

        $this->assertEquals( 2, count($result));
        $this->assertEquals( 'Test2', get_class($result[0]));
        $this->assertEquals( 'test1', $result[0]->nom);
        $this->assertEquals( 'test2', $result[1]->nom);
    }

    public function testAllSuccess() {
        $result = Test::all();

        $this->assertEquals( 2, count($result));
        $this->assertEquals( 'test1', $result[0]->nom);
        $this->assertEquals( 'test2', $result[1]->nom);
    }

    public function testSelectOk() {
        $result = Test::where( "nom", "=", "test2")->get();

        $this->assertEquals( 1, count($result));
        $this->assertEquals( 2, $result[0]->id);
    }

    public function testSaveInsert() {
		// En premier lieu on regarde si la sequence est intialisée
		if(Test::getSequence() === 0 ) {
			Test::updateSequence();
		}
		
        $obj = new Test();
        $obj->nom = 'test ajouté 1';
        $obj->description = 'un test ajouté';

        $obj->save();

        $controle = Test::find($obj->id);

        $this->assertGreaterThan(0, $obj->id);
        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertEquals( $obj->description, $controle->description);
    }

    public function testSaveUpdate() {
        $obj = Test::find(1);

        $obj->description = 'test modifié';
        $obj->save();

        $controle = Test::find(1);

        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertEquals( $obj->description, $controle->description);
    }

    public function testSaveInsertAvecUnNullImplicite() {
        $obj = new Test();
        $obj->nom = 'test ajouté 1';
        $obj->description = null;

        $obj->save();

        $controle = Test::find($obj->id);

        $this->assertGreaterThan(0, $obj->id);
        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertNull( $controle->description);

    }

    public function testSaveUpdateAvecUnNullImplicite() {
        $obj = Test::find(1);

        $obj->description = null;
        $obj->save();

        $controle = Test::find(1);

        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertNull( $controle->description);

    }

    public function testInsertDUneDate() {
        $obj = new Test();
        $obj->nom = 'test ajouté 2';
        $obj->description = 'un test ajouté';
        $now = time();
        $obj->date = $now;

        $obj->save();

        $controle = Test::find($obj->id);

        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertEquals( $now, $controle->date);
    }

    public function testSaveInsertAvecTimeStamp() {
        $obj = new Test();
        $obj->nom = 'test ajouté 1';
        $obj->date = 7200;
        $obj->save();

        $controle = Test::find($obj->id);

        $this->assertGreaterThan(0, $obj->id);
        $this->assertEquals( $obj->nom, $controle->nom);
        $this->assertEquals( 7200, $controle->date);

    }

    // @TODO: test Dirty
}

