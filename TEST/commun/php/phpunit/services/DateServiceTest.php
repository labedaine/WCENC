<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";
require_once $HOME."/../tests/test_commun/Utils.php";

class DateServiceTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        App::initialise();
        Utils::initFakeServeur();
        SinapsApp::singleton("TimeService", function() { return new TimeService();});
    }

    public function __construct() {

    }

    public function testDateJourHNO_HO() {
        $dateService = new DateService();

        $this->assertFalse( $dateService->isHO(1388585653), "Jour de l'an 2014"); // Jour de l'an 2014
        $this->assertFalse( $dateService->isHO(1419516853), "Noel 2014");
        $this->assertFalse( $dateService->isHO(1415715253), "11/11/2014 WW1");
        $this->assertFalse( $dateService->isHO(1398953653), "08/05/2014 WW2");
        $this->assertFalse( $dateService->isHO(1405347253), "14/07/2014 Fete Nationale");
        $this->assertFalse( $dateService->isHO(1408112053), "15/08/2014 Assomption");
        $this->assertFalse( $dateService->isHO(1414851253), "01/11/2014 Toussaint");
        $this->assertFalse( $dateService->isHO(1398953653), "01/05/2014 Travail");

        $this->assertFalse( $dateService->isHO(1398089653), "21/04/2014 Paques 2014");
        $this->assertFalse( $dateService->isHO(1401372853), "29/05/2014 Ascension 2014");
        $this->assertFalse( $dateService->isHO(1402323253), "08/06/2014 Lundi Pentecôte 2014");

        $this->assertFalse( $dateService->isHO(1398143640), "22/04/2014 07h14:13 Lendemain de Paques 2014");
        $this->assertTrue( $dateService->isHO(1398168840), "22/04/2014 14h14:13 Lendemain de Paques 2014");

        $this->assertFalse( $dateService->isHO(1391260440), "22/04/2014 14h14:13 WE");

        //$this->assertTrue();

    }

}
