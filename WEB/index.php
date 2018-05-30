<?php
/**
 *
 * ihm restitution
 *
 * PHP version 5
 *
 * @author Philippe Jung <philippe-1.jung@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/ressource/php/Autoload.php";
require_once __DIR__."/ressource/php/SinapsApp.php";

require_once __DIR__."/route.php";
require_once __DIR__."/Autoload.php";

SinapsApp::initialise(dirname(__FILE__).'/config/');

// Register des services spécifiques à la restitution
SinapsApp::bind(
    "FileService",
    function () {
        $objFileService = new FileService();
        return $objFileService;
    }
);
SinapsApp::bind(
    "ParisService",
    function () {
        $objParisService = new ParisService();
        return $objParisService;
    }
);
SinapsApp::bind(
    "SystemService",
    function () {
        $objSystemService = new SystemService();
        return $objSystemService;
    }
);

SinapsApp::bind(
    "UtilisateurService",
    function () {
        $objUtilisateurService = new UtilisateurService();
        return $objUtilisateurService;
    }
);

SinapsApp::bind(
    "GroupeService",
    function () {
        return new GroupeService();
    }
);

SinapsApp::bind(
    "LoginService",
    function () {
        $objLoginService = new LoginService();
        return $objLoginService;
    }
);

SinapsApp::singleton(
    "RestClientService",
    function() {
        $objRestClientService = new RestClientService();
        return new RestClientService();
    }
);

SinapsApp::register("FileService");
SinapsApp::register("SystemService");
SinapsApp::register("RestClientService");
SinapsApp::register("ParisService");

if (!Route::resolve($_SERVER['REQUEST_URI'])) {
    print "<h1>404</h1>";
}
?>
