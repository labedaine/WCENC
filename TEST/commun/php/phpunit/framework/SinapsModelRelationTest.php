<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";

// @TODO: Réorder
require_once __DIR__.'/TestModelM.php';
require_once __DIR__.'/TestModel1N.php';
require_once __DIR__.'/TestModel11.php';

/**
 * Schéma:
*/

class SinapsModelRelationsTest extends PHPUnit_Framework_TestCase {
    static public $dbh;

     public function setUp() {
        $server = getenv("DB_SERVER");
        self::$dbh = new PDO (  "pgsql:host=".$server.";dbname=test_r",
                                "test",
                                "test",
                                 array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                                        PDO::ATTR_PERSISTENT => true));

        self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->dropTables();

        $this->initTestM();
        $this->initTest1N();
        $this->initTest11();

        App::initialise();
        App::singleton( 'dbConnection', function() {
            return SinapsModelRelationsTest::$dbh;
        });
    }

    public function initTestM() {
        self::$dbh->query('
            CREATE TABLE "TestM" (
                    "id" SERIAL,
                    "nom" VARCHAR(255) NOT NULL ,
                    "description" VARCHAR(8192) NULL DEFAULT NULL,
                    PRIMARY KEY ("id"))');
        self::$dbh->query('CREATE INDEX "idx_TestM" ON "TestM" ( "id" ASC NULLS LAST)');

        self::$dbh->query('
            INSERT INTO "TestM" VALUES( 1, \'test1\', NULL)');
        self::$dbh->query('
            INSERT INTO "TestM" VALUES( 2, \'test2\', \'Commentaire 1\')');
        
        // On actualise l'id de séquence   
		$query = new OrmQuery("TestM", null,self::$dbh );
        $query->updateSequence();
    }

    public function initTest1N() {
        self::$dbh->query('
            CREATE TABLE "Test1N" (
                    "id" SERIAL,
                    "clef" VARCHAR(255) NOT NULL ,
                    "valeur" VARCHAR(255) NULL DEFAULT NULL,
                   "TestM_id" INTEGER NULL,
                    PRIMARY KEY ("id"),
                    CONSTRAINT "fk_Test1N_TestM1"
                        FOREIGN KEY ("TestM_id" )
                        REFERENCES "TestM" ("id" )
                        ON DELETE CASCADE
                        ON UPDATE NO ACTION)
        ');
        self::$dbh->query('CREATE INDEX "idx_Test1N" ON "Test1N" ( "id" ASC NULLS LAST)');
        self::$dbh->query('CREATE INDEX "fk_Test1N_TestM1_idx" ON "Test1N" ( "TestM_id" ASC NULLS LAST)');

        self::$dbh->query('
            INSERT INTO "Test1N" VALUES( 1, \'nom\', \'bob\', 1)');
        self::$dbh->query('
            INSERT INTO "Test1N" VALUES( 2, \'prenom\', \'bill\', 1)');

        self::$dbh->query('
            INSERT INTO "Test1N" VALUES( 3, \'nom\', \'john\', 2)');
        
        // On actualise l'id de séquence   
		$query = new OrmQuery("Test1N", null,self::$dbh);
        $query->updateSequence();
    }

    public function initTest11() {
        self::$dbh->query('
            CREATE TABLE "Test11" (
                    "id" SERIAL,
                    "login" VARCHAR(255) NOT NULL ,
                    "TestM_id" INT NULL,
                    "Test1N_id" INT NULL,
                    PRIMARY KEY ("id"),
                    CONSTRAINT "fk_Test11_TestM1"
                        FOREIGN KEY ("TestM_id" )
                        REFERENCES "TestM" ("id" )
                        ON DELETE CASCADE
                        ON UPDATE NO ACTION,
                    CONSTRAINT "fk_Test11_Test1N1"
                        FOREIGN KEY ("Test1N_id" )
                        REFERENCES "Test1N" ("id" )
                        ON DELETE CASCADE
                        ON UPDATE NO ACTION
            )
        ');
        self::$dbh->query('CREATE INDEX "fk_Test11_TestM1_idx" ON "Test11" ( "TestM_id" ASC NULLS LAST)');
        self::$dbh->query('CREATE INDEX "fk_Test11_Test1N1" ON "Test11" ( "Test1N_id" ASC NULLS LAST)');

        self::$dbh->query("
            INSERT INTO \"Test11\" VALUES( 1, 'login1', 1, 2)");
        self::$dbh->query("
            INSERT INTO \"Test11\" VALUES( 2, 'login2', 2, 3)");
            
        // On actualise l'id de séquence   
		$query = new OrmQuery("Test11", null,self::$dbh);
        $query->updateSequence();
    }

    public function tearDown() {
        $this->dropTables();
    }

    public function dropTables() {

        try {
			self::$dbh->query( "DROP TABLE IF EXISTS \"Test11\"" );
			self::$dbh->query( "DROP TABLE IF EXISTS \"Test1N\"");
			self::$dbh->query( "DROP TABLE IF EXISTS \"TestM\"");
		} catch(PDOException $e) {
            echo $e;
        }
    }

    public function testHasOnePresent() {
        $login = TestM::find(1)->test11;
        $this->assertEquals( "login1", $login->login);
    }

    public function testBelongsToPresent() {
        $testM = Test11::find(1)->testM;

        $this->assertEquals( "test1", $testM->nom);
    }

    public function testHasManyPresent() {

        $test1N = TestM::find(1)->test1N;

        $this->assertEquals( 2, count($test1N));
        $this->assertEquals( "nom", $test1N[0]->clef);
        $this->assertEquals( "prenom", $test1N[1]->clef);

    }

    public function testHasManyReverse() {
        $testM = Test1N::find(3)->testM;

        $this->assertEquals( "test2", $testM->nom);
    }

    public function testInsertHasOne() {
		if(TestM::getSequence() === 0 ) {
			TestM::updateSequence();
		}
        $testM = new TestM();
        $testM->nom = "insert1";
        $testM->save();

		if(Test11::getSequence() === 0 ) {
			Test11::updateSequence();
		}

        $test11 = new Test11();
        $test11->login = "toto";
        $testM->test11()->save($test11);

        $controle = TestM::find($testM->id)->test11;

        $this->assertEquals( "toto", $controle->login);
    }

    public function testInsertBelongsTo() {
        $test11 = new Test11();
        $test11->login = "titi";
        $test11->save();

        $testM = TestM::find(1);

        // Détachement de l'ancienne assoc
        $oldTest11 = $testM->test11;
        $oldTest11->TestM_id = null;
        $oldTest11->save();

        $test11->testM()->associate($testM);

        $controle = TestM::find(1)->test11;

        $this->assertEquals( "titi", $controle->login);
    }

    public function testWith1NDirectFind() {
        $test11 = TestM::with("test1N")->find(1);

        $this->couperConnection();

        $this->assertEquals(2, count($test11->test1N));
    }

    public function testWith1NDirectGet() {
        $allTests = TestM::with("test1N")->get();

        $this->couperConnection();

        $this->assertEquals(2, count($allTests[0]->test1N));
        $this->assertEquals(1, count($allTests[1]->test1N));
    }

    public function testWith11DirectGet() {
        $allTests = TestM::with("test11")->get();

        $this->couperConnection();

        $this->assertEquals( "login1", $allTests[0]->test11->login);
        $this->assertEquals( "login2", $allTests[1]->test11->login);
    }

    public function testWith11ReverseGet() {
        $allTests = Test11::with("testM")->get();

        $this->couperConnection();

        $this->assertEquals( "test1", $allTests[0]->testM->nom);
        $this->assertEquals( "test2", $allTests[1]->testM->nom);
    }

    public function testWithMultiRelationsGet() {
        $allTests = TestM::with("test11,test1N")->get();

        $this->couperConnection();

        $this->assertEquals(2, count($allTests[0]->test1N));
        $this->assertEquals(1, count($allTests[1]->test1N));
        $this->assertEquals( "login1", $allTests[0]->test11->login);
        $this->assertEquals( "login2", $allTests[1]->test11->login);
    }

    public function testWithRelationA2NiveauxGet() {
        $allTests = TestM::with("test11.test1N")->get();

        $this->couperConnection();

        $this->assertEquals( "bill", $allTests[0]->test11->test1N->valeur);
        $this->assertEquals( "john", $allTests[1]->test11->test1N->valeur);
    }

    public function testWithRelationA2Niveaux1NGet() {
        $allTests = TestM::with("test1N.test11")->get();

        $this->couperConnection();

        $this->assertEquals( "login1", $allTests[0]->test1N[1]->test11->login);
        $this->assertEquals( "login2", $allTests[1]->test1N[0]->test11->login);
    }

    public function testWithRelationAvecWhere() {
        $allTests = TestM::with("test1N")->where( "id", 2)->get();

        $this->couperConnection();

        $this->assertEquals( "john", $allTests[0]->test1N[0]->valeur);
    }

    public function testJeMetAJourUneRelationEtJeLaRecupere() {
        $test = TestM::find(1);
        $test11_2 = Test11::find(2);

        $test->test11()->save($test11_2);

        $this->assertEquals( "login2", $test->test11->login);
    }

    private function couperConnection() {
        App::singleton( 'dbConnection', function() {
            return null;
        });
    }
}
