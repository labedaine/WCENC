<?php
/**
 * Ensemble de fonctions liées à la gestion des applications.
 *
* PHP version 5
*
 * @author MSN-Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
*/

use models\configuration\Application;

class ApplicationService {

    protected $jqGridService;

    private $namespace = "";

    private $classApplication;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->jqGridService = SinapsApp::make("JqGridService");

        $this->classApplication = SinapsApp::$dataNamespace . "\Application";

        $this->dbh = SinapsApp::make("dbConnection");
    }

    /**
     * fonction de position du directory sur apps/
     */
    public function repertoireApps() {
        $test = explode("/", __DIR__);
        $cpt = count($test)-1;
        while ($test[$cpt] !== "apps") {
            array_pop($test);
            $cpt--;
        }
        $dir = "";
        $dir = implode("/", $test);
        return $dir;
    }

}
