<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\Context,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\FeatureEvent;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use \Mockery as m;

$rootDir = __DIR__."/../../../WEB";
require_once $rootDir."/ressource/php/Autoload.php";
require_once $rootDir."/../TEST/test_commun/Utils.php";
require_once $rootDir."/Autoload.php";

//~ require_once $rootDir."/ressource/php/services/TimeService.php";
require_once $rootDir.'/ressource/php/framework/Log.php';

$heureCourante = 0;

/**
 * Features context.
 */

class ApiServiceSubContext implements Context {
    private $now = FALSE;

    // Pour les recherches dans les fichiers
    static $fileBuffer;

    // Pour le sign-in
    private $mock=NULL;
    private $token=NULL;
    private $api=NULL;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct() {
        $this->apiController = new ApiFootballDataController();
    }

    /** @BeforeScenario */
    public function before($event) {

        // Récupération du père
        $environment = $event->getEnvironment();
        $this->mainContext = $environment->getContext('FeatureContext');
    }

    public function getPayload() {

        $retour = json_decode($this->mock->toJSON());
        return json_decode($retour->payload);
    }

    /** ***************************************************************************
     * SIMULATION DE CONNEXION AVEC L'API
     *****************************************************************************/

    /**
     * @Given /^je lis le fichier (\S+)$$/
     */
    public function jeLisLeFichier($fichier) {

        $this->mock = MockedRestClient::getInstance();
        $this->mock->callFakeFootballApi($fichier);

        $this->api = new ApiFootballDataService();
        $this->api->getMatchById(0);
    }

    /**
     * @Given /^il est correct$/
     */
    public function ilEstCorrect() {

         $retour = $this->getPayload();
         assertEquals($retour->fixture->_links->competition->href, "http://api.football-data.org/v1/competitions/467");

         $this->mock->close();
    }

    /**
     * @Given /^je récupère (\d+) matchs$/
     */
    public function jeRecupereXMatch() {

         //echo "heure dans jeRecupereXMatch: ".$this->mainContext->timeService->now() . "\n";

         SinapsApp::$config["log.api.writers"] = 'MemoryLogWriter';
         SinapsApp::$config["log.api.niveau"] = '0';

         SinapsApp::registerLogger("ApiLogger", "api");

         $this->apiController->getMatchDansLHeure();
         $retour = $this->getPayload();

         assertEquals($retour->fixture->_links->competition->href, "http://api.football-data.org/v1/competitions/467");

         $this->mock->close();
    }



/*
 *
 *
 *           $donnneCollectee->date = App::make("TimeService")->now();

 *
 *
 *  SinapsApp::$config["log.import_evenement_exterieur.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.import_evenement_exterieur.niveau"] = '0';

        SinapsApp::registerLogger("ImportEvenementExterieurLogger", "import_evenement_exterieur");




        $mockedGestionForceCollecteService = m::mock("GestionForceCollecteService")
                                              ->shouldReceive("check")
                                              ->andReturnUsing($check)
                                              ->getMock();
        SinapsApp::singleton(
            "GestionForceCollecteService",
            function () use ($mockedGestionForceCollecteService) {
                return $mockedGestionForceCollecteService;
            }
        );

        $service = SinapsApp::make('GestionForceCollecteService');
        $cmdNagios = $service->check();*/

}
