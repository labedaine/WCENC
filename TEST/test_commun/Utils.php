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
require_once __DIR__."/TestDataRestitutionStd.php";
require_once __DIR__."/FakeServer.php";

require_once __DIR__."/../../WEB/ressource/php/services/TimeService.php";


class Utils {
    static $currentTime;

    static public function initFakeServeur($databaseType="POSTGRESQL") {
        return FakeServer::init($databaseType);
    }

    static public function truncateSession() {
        return FakeServer::truncateSession();
    }

    static public function initFakeServeurConfiguration($databaseType="POSTGRESQL") {
        return FakeServerConfiguration::init($databaseType);
    }

    static public function initFakeMailService() {
        return FakeMailService::init();
    }

    static public function populate($besoinDHistorique=FALSE) {
        TestDataRestitutionStd::populate($besoinDHistorique);
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

    static function analyserTablesDeVerite(TableNode $tdv) {
        $result = array();

        $formulePartielle = array();
        $typeDeTable = TableDeVeriteExtension::ET;
        $msg = NULL;
        $cardinalite = 0;
        foreach( $tdv->getHash() as $ligneDeTdv) {
            if ( $ligneDeTdv["Op"] == "ET") {
                $typeDeTable = TableDeVeriteExtension::ET;
            }
            if (preg_match("/(\d+)ParmiN/", $ligneDeTdv["Op"], $matches)) {
                $typeDeTable = TableDeVerite::X_PARMI_N;
                $cardinalite = $matches[1][0];
            }
            if( !empty($ligneDeTdv["Message"]))
                $msg = $ligneDeTdv["Message"];

            if( $ligneDeTdv["Op"] === "OU") {
                $result[] = self::creerUneTableDeVerite($typeDeTable, $cardinalite, $formulePartielle, $msg);
                $formulePartielle = array();
                continue; // On n'ajoute pas cette ligne qui est vide
            }

            $formulePartielle[] = $ligneDeTdv;
        }

        if (count($formulePartielle) > 0 )
            $result[] = self::creerUneTableDeVerite($typeDeTable, $cardinalite, $formulePartielle, $msg);

        return $result;
    }

     static function creerUneTableDeVerite($type, $xParmiN, $indicateurs, $msg) {
        $tdv = array();
        $tdv['type'] = $type;
        $tdv['XParmiN'] = $xParmiN;
        $tdv['message'] = $msg;
        $prepareFormule = array();
        foreach( $indicateurs as $indicateur) {
            $critical = explode(" ", $indicateur["Critical"]);
            $warning = explode(" ", $indicateur["Warning"]);
            $ok = explode(" ", $indicateur["Ok"]);
            $unknown = explode(" ", $indicateur["Unknown"]);

            $prepareFormule[] = array(
                "name" => $indicateur["Source"],
                "critical_comp" => $critical[0],
                "critical_value" => ((count($critical) > 1) ? $critical[1] : ""),
                "warning_comp" => $warning[0],
                "warning_value" => ((count($warning) > 1) ? $warning[1] : ""),
                "ok_comp" => $ok[0],
                "ok_value" => ((count($ok) > 1) ? $ok[1] : ""),
                "unknown_comp" => $unknown[0],
                "unknown_value" => ((count($unknown) > 1) ? $unknown[1] : "")
            );
        }

        $tdv['formule'] = json_encode($prepareFormule);

        return $tdv;
    }


}
