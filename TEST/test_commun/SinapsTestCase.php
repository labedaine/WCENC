<?php
/**
 * Ajoute des méthodes pour les tests de payload SINAPS.
 *
 * PHP Version 5
 *
 * @author CGI <cgi@cgi.com>
 */

abstract class SinapsTestCase extends PHPUnit_Framework_TestCase {

    public function assertIsJson($response) {
        $this->assertNotNull(json_decode($response), "Le contenu n'est pas un contenu Json valide");
    }

    public function assertSuccess($response, $expectedValue=NULL) {
        $this->assertIsJson($response);
        $obj = json_decode($response, TRUE);
        $this->assertArrayHasKey("success", $obj, "Success n'est pas présent");
        if ($expectedValue !== NULL) {
            $this->assertEquals($expectedValue, $obj["success"], "La valeur de success n'est pas celle attendue");
        }
    }

    public function assertPayloadEquals($response, $expectedValue) {
        $this->assertIsJson($response);
        $obj = json_decode($response, TRUE);
        $this->assertArrayHasKey("payload", $obj, "payload n'est pas présent");
        $this->assertEquals($expectedValue, $obj["payload"], "La valeur de payload n'est pas celle attendue");
    }

    public function assertPayloadContainsKey($response, $key, $expectedValue=NULL) {
        $this->assertIsJson($response);
        $obj = json_decode($response, TRUE);
        $this->assertArrayHasKey("payload", $obj, "payload n'est pas présent");
        $this->assertArrayHasKey($key, $obj["payload"], "Clé absente du payload");
        if ($expectedValue !== NULL) {
            $this->assertEquals(
                $expectedValue,
                $obj["payload"][$key],
                "La valeur de la clé recherchée n'est pas celle attendue"
            );
        }
    }
}
