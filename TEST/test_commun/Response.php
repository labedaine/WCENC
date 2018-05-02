<?php
/**
 * Emule la classe Response.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

class Response {
    static $code = 200;
    static $response = "";

    static public function abort($httpCode, $httpMessage="") {
        static::$code = $httpCode;
    }

    static public function addContent($texte) {
        static::$response .= $texte;
    }

    static public function reset() {
        static::$code = 200;
        static::$response = "";
    }
}
