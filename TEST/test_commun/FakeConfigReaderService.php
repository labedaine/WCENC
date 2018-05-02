<?php
/**
 * Permet de lire une configuration arbitraire en lieu et place du fichier de config.
 *
 * PHP Version 5
 *
 * @author CGI <cgi@cgi.com> 
 */
 
class FakeConfigReaderService {
    public function readConfigOnInstance() {
        return array();
    }

    public static function init() {
        \ConfigReaderService::init(new FakeConfigReaderService());
    }
}
