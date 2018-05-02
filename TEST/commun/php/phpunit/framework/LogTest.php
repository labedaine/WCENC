<?php
/**
 * Classe LogTest.php.
 *
 * PHP version 5
 *
 * @author inconnu <inconnu@dgfip.finances.gouv.fr>
 */

$rootDir = __DIR__. "/../../../../../apps";
require_once $rootDir . "/commun/php/Autoload.php";
require_once $rootDir . "/commun/php/framework/log_writers/MemoryLogWriter.php";
require_once $rootDir . "/commun/php/framework/log_writers/NullLogWriter.php";
require_once $rootDir . "/../tests/test_commun/Utils.php";

class LogTest extends PHPUnit_Framework_TestCase {
    public function __construct() {
    }

    public function setUp() {
        App::initialise();
    }

    public function tearDown() {

    }

    /**
     * Le simple fait de ne pas planter est un test du comportement par defaut
     */
    public function testComportementParDefaut() {
        $sut = new Log2("test");
        $sut->addDebug("test");
        $sut->addWarning("test");
        $sut->addInfo("test");
        $sut->addError("test");
    }

    public function testMemoryWriter() {
        $sut = new Log2("test");
        $sut->pushHandler(new MemoryLogWriter());

        $sut->addError("une erreur");

        $this->assertContains("une erreur", $sut->dump("error"));
    }

    public function testNullWriter() {
        $sut = new Log2("test");
        $sut->pushHandler(new NullLogWriter());

        $sut->addError("une erreur");

        $this->assertEmpty($sut->dump("error"));
    }

    public function testUtilisationFichierIniPourWriters() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter");

        $sut = new Log2("test");

        $sut->addError("une erreur");

        $this->assertContains("une erreur", $sut->dump("error"));
    }

    public function testBaseFormater() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "BaseLogFormat");

        $sut = new Log2("test");

        $sut->addError("une erreur");

        $this->assertContains("[error] une erreur", $sut->dump("error"));
    }

    public function testTimeFormater() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "TimeLogFormat");

        Utils::fakeTime(2 * 3600);

        $sut = new Log2("test");

        $sut->addError("une erreur");
        $this->assertContains("[1970-01-01 03:00:00]\tune erreur", $sut->dump("error"));
    }

    public function testMemoryFormater() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "MemoryLogFormat");

        Utils::fakeTime(2 * 3600);

        $sut = new Log2("test");

        $sut->addError("une erreur");

        $logs = $sut->dump("error");
        $this->assertRegexp("/Mem:\S+ \w+ une erreur/", $logs[0]);
    }

    public function testTimeFormaterFormatHMS() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "TimeLogFormat![h:i:s]");

        Utils::fakeTime(2 * 3600);

        $sut = new Log2("test");

        $sut->addError("une erreur");
        $this->assertContains("[03:00:00]\tune erreur", $sut->dump("error"));
    }

    public function testChaineDeFormat() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "TimeLogFormat!h:i:s,BaseLogFormat");

        Utils::fakeTime(2 * 3600);

        $sut = new Log2("test");

        $sut->addError("une erreur");
        $this->assertContains("[error] 03:00:00\tune erreur", $sut->dump("error"));
    }

    public function testFileWriter() {  
        $fichierDeLog = __DIR__."/testSinaps.log";
        SinapsApp::$config = array( "log.test.writers" => "FileLogWriter!$fichierDeLog",
                                    "log.test.formats" => "BaseLogFormat");

        if (file_exists($fichierDeLog)) {
            unlink($fichierDeLog);
        }

        $sut = new Log2("test");

        $sut->addError("une erreur");
        $this->assertFileExists($fichierDeLog);
        $this->assertContains("[error] une erreur", file_get_contents($fichierDeLog));
    }       

    public function testChaineDeWriter() {  
        $fichierDeLog = __DIR__."/testSinaps.log";
        SinapsApp::$config = array( "log.test.writers" => "FileLogWriter!$fichierDeLog,MemoryLogWriter",
                                    "log.test.formats" => "BaseLogFormat");

        if (file_exists($fichierDeLog)) {
            unlink($fichierDeLog);
        }

        $sut = new Log2("test");

        $sut->addError("une erreur");
        $this->assertFileExists($fichierDeLog);
        $this->assertContains("[error] une erreur", file_get_contents($fichierDeLog));
        $this->assertContains("[error] une erreur", $sut->dump("error"));
    }

    public function testEtapesBaseLogFormat() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "BaseLogFormat");

        $sut = new Log2("test");

        $sut->debuterEtape("etape");
        $sut->finirEtape(NULL, "etape");
        $this->assertContains("[info] ***etape*** ", $sut->dump("info"));
    }

    public function testEtapesMemoryLogFormat() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "MemoryLogFormat!noFormat,BaseLogFormat");

        $sut = new Log2("test");

        $sut->debuterEtape("consoMemoire");
        $consommonsDeLaMemoire = array();
        for($i = 0; $i < 10000; $i++) {
            $consommonsDeLaMemoire[$i] = "kdhqfkjhkgjqhdlkgqlkfjqlgjqsdljkdflqkhfqjkl";
        }
        $sut->finirEtape(NULL, "consoMemoire");

        $logs = $sut->dump("info");
        $this->assertRegexp("/\*\*\*consoMemoire\*\*\* DeltaMem:\d+/", $logs[0]);
    }
    
    public function testEtapesTimeLogFormat() {
        SinapsApp::$config = array( "log.test.writers" => "MemoryLogWriter",
                                    "log.test.formats" => "TimeLogFormat,BaseLogFormat");
        Utils::fakeTime(2 * 3600);

        $sut = new Log2("test");

        $sut->debuterEtape();
        Utils::$currentTime = 2 * 3600 + 20;
        $sut->finirEtape("fini!");

        $logs = $sut->dump("info");
        $this->assertRegexp("/DeltaTemps:20/", $logs[0]);
    }
}