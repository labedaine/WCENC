<?php
/**
 * Classe Utils.php.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../tools/behat/vendor/autoload.php";
use \Mockery as m;
use Behat\Gherkin\Node\TableNode;

require_once __DIR__."/SinapsTestCase.php";
require_once __DIR__."/FakeConfigReaderService.php";
require_once __DIR__."/FakeInput.php";
require_once __DIR__."/FakeMailService.php";
require_once __DIR__."/bootstrap/FakeCookie.php";
require_once __DIR__."/Response.php";
require_once __DIR__."/MockedRestClient.php";
require_once __DIR__."/FakeServer.php";

require_once __DIR__."/../../WEB/ressource/php/services/TimeService.php";


class Utils {
    static $currentTime;

    static public function initFakeServeur() {
        return FakeServer::init();
    }

    static public function initFakeMailService() {
        return FakeMailService::init();
    }

    static function fakeTime($time) {
        static::$currentTime = $time;
        $timeServiceMock = m::mock("TimeService")
            ->shouldReceive("now")
            ->andReturnUsing(
                function () {
                    return Utils::$currentTime;
                }
            )->getMock();

        App::singleton(
            "TimeService",
            function () use ($timeServiceMock) {
                return $timeServiceMock;
            }
        );
    }

    static public function truncateAll() {
        FakeServer::truncateAll();
    }
}
