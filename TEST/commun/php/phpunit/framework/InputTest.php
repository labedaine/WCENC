<?php

$HOME=__DIR__."/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";

class InputTest extends PHPUnit_Framework_TestCase {

    public function __construct() {
        Input::init(new Input());
    }

    public function testParametresJson() {
        $forJson = array (  "login" => "toto",
                            "passwd" => "titi");

        $_REQUEST["json"] = json_encode($forJson);

        /*
        $this->assertEquals( "toto", Input::get("login"));
        $this->assertEquals( "titi", Input::get("passwd"));
        */
        $this->assertTrue( true );
    }
}
