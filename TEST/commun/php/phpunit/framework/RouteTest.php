<?php

$HOME=__DIR__."/../../../../../apps";
require_once $HOME."/commun/php/Autoload.php";

require_once $HOME."/../tests/test_commun/Utils.php";

require_once __DIR__."/controllers/TestController.php";

$RESULT = "";

class RouteTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        global $RESULT;
        $RESULT = "";

        Utils::initFakeServeur();
        Route::reset();
    }

    public function tearDown() {
    }

    public function testGetRouteEnDur() {
        global $RESULT;
        Route::get("/users", "TestController@getPing");

        Input::set( "METHOD", "GET");
        $result = Route::resolve("/users");

        $this->assertEquals( "getPong", $RESULT);
    }

    public function testPostRouteEnDur() {
        global $RESULT;
        Route::post("/users", "TestController@postPing");

        Input::set( "METHOD", "POST");
        $result = Route::resolve("/users");

        $this->assertEquals( "postPong", $RESULT);
    }


    public function testPutRouteEnDur() {
        global $RESULT;
        Route::put("/users", "TestController@putPing");

        Input::set( "METHOD", "PUT");
        $result = Route::resolve("/users");

        $this->assertEquals( "putPong", $RESULT);
    }


    public function testDeleteRouteEnDur() {
        global $RESULT;
        Route::delete("/users", "TestController@deletePing");

         Input::set( "METHOD", "DELETE");
         $result = Route::resolve("/users");

        $this->assertEquals( "deletePong", $RESULT);
    }

    public function testJeDeclareUnGetMaisJeFaisUnPost() {
        global $RESULT;
        Route::get("/users", "TestController@getPing");

        Input::set( "METHOD", "POST");
        $result = Route::resolve("/users");

        $this->assertFalse($result);

    }

    public function testGetEtDeleteSurMemeUrl() {
        global $RESULT;
        Route::get("/users", "TestController@getPing");
        Route::delete("/users", "TestController@deletePing");

        Input::set( "METHOD", "DELETE");
        $result = Route::resolve("/users");

        $this->assertEquals( "deletePong", $RESULT);

    }

/*    public function testRegisterControllerSimple() {
        Route::controller("TestController");

        $result = Route::resolve( "/Test/ping", $_SERVER['REQUEST_METHOD']);

        $this->assertEquals( "pong", $result);
    }*/
}
