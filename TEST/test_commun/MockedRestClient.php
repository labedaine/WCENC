<?php
/**
 * Permet de simuler un appel web service.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

use \Mockery as m;

class MockedRestClient {
    public $success = TRUE;
    public $code = 0;
    public $payload = "ok";

    public static $instance;

    public static function initMock() {
        self::$instance = new MockedRestClient();

        $mockedRestClientService = m::mock("RestClientService")
                                    ->shouldReceive("getURL")
                                    ->andReturnUsing(
                                        function () {return MockedRestClient::getInstance()->toJSON();}
                                    )

                                    ->shouldReceive("throwExceptionOnError")
                                    ->andReturn(TRUE)

                                    ->getMock();
        SinapsApp::bind(
            "RestClientService",
            function () use ($mockedRestClientService) {
                return $mockedRestClientService;
            }
        );
    }

    public static function getInstance() {
        if (!self::$instance)
            self::initMock();

        return self::$instance;
    }

    public function toJSON() {
        $reponse = array(
            "success" => $this->success,
            "code" => $this->code,
            "payload" => (is_callable($this->payload) ? call_user_func($this->payload) : $this->payload)
        );

        return json_encode($reponse);
    }

    public function callRepartitionService() {
        self::getInstance()->payload = function () {
            $repartitionService = new RepartitionService(array(), TRUE);
            $repartitionService->repartirDomaines();
        };
    }

    public static function close() {
        SinapsApp::register("RestClientService");
        self::$instance = NULL;
    }
}