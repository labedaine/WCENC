<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\Context,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Hook\Scope\AfterFeatureScope,
    Behat\Behat\Hook\Scope\BeforeFeatureScope,
    Behat\Behat\Hook\Scope\AfterStepScope,
    Behat\Testwork\Tester\Result\TestResult;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use \Mockery as m;

//require_once 'PHPUnit/Autoload.php';
//require_once 'PHPUnit/Framework/Assert/Functions.php';

require_once __DIR__."/../../tools/behat/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php";
//~ use PHPUnit\Framework\Assert\Functions;
use PHPUnit\Framework\Assert;

$rootDir = __DIR__."/../../../WEB";
require_once $rootDir."/ressource/php/Autoload.php";
require_once $rootDir."/../TEST/test_commun/Utils.php";

require_once $rootDir."/ressource/php/services/TimeService.php";

require_once $rootDir.'/service/UtilisateurService.php';
require_once $rootDir.'/controller/UtilisateurController.php';
require_once $rootDir.'/ressource/php/framework/log_writers/FileLogWriter.php';

$heureCourante = 0;

/**
 * Features context.
 */

class FeatureContext implements Context
{
    private $phaseFinale = 1;
    private $now = FALSE;
    private $loggerName;

    // Pour les recherches
    static $fileBuffer;

    // Pour le sign-in
    private $token;

    // Pour le code retour du login
    private $codeRetour;



    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct() {
        date_default_timezone_set("Europe/Paris");

        SinapsApp::initialise(__DIR__ . '/../../../WEB/ressource/php/config');

        SinapsApp::bind(
            "UtilisateurService",
            function () {
                return new UtilisateurService();
            }
        );
    }

    /** @BeforeFeature */
    public static function setupFeature(\Behat\Behat\Hook\Scope\BeforeFeatureScope $event) {

    }

    /** @AfterFeature */
    public static function tearDownFeature(\Behat\Behat\Hook\Scope\AfterFeatureScope $event) {
        //Utils::truncateAll();
    }


    /** @BeforeScenario */
    public function before($event) {

        Utils::initFakeServeur();
        //Utils::truncateAll();

        // Récupération des différents contexts
        $environment = $event->getEnvironment();
        //var_dump($environment);
        $this->populateContext = $environment->getContext('PopulateSubContext');
        // $this->populateContext->saveUneTableDeVerite($ie, TableDeVerite::ET, 0, $indicateurs, "", 0);
    }

    /** @BeforeStep */
    public function beforeEtape($event) {

    }

    /**
     * Logue le log courant si erreur
     *
     * @AfterStep
     */
    public function printLogAfterFailedStep( AfterStepScope $scope) {

    }


    /** @AfterScenario */
    public function after($event) {
        //Utils::truncateAll();
    }

    /** @BeforeStep */
    public function beforeStep($event) {

        SinapsApp::reset();
        Cookie::reset();
        Input::reset();

        if ($this->token) {
            Cookie::set("token", $this->token);
        }
    }

    /** @AfterScenario */
    public function afterScenario($event) {
        Response::reset();
    }

    public function getToken() {
        return $this->token;
    }

    public function setInCookie($clef, $valeur) {
        Cookie::set($clef, $valeur);
    }

    public function getFromCookie($clef) {
        return Cookie::get($clef);
    }

    public function setUtilisateurCourant(Utilisateur $user) {
        SinapsApp::setUtilisateurCourant($user);
    }

    /** *******************************************************
     * Générique
     *
     * Les phrases présentes ici sont très générique et ont pour
     * but de permettre de s'abstraire de la logique interne du
     * produit
     ******************************************************** */


    /** *******************************************************
     * TEMPS
     ******************************************************** */

    /**
     * @Given /^il est (\d+)h(\d+):(\d+)$/
     */

    public function ilEstTemps($heures, $minutes, $secondes) {
        global $heureCourante;

        // Note: le temps commence à jeudi 1970-01-01 01:00:00
        $time = mktime(0, 0, 0, 6, 14, 2018);
        $heureCourante = ($heures) * 3600 + $minutes * 60 + $secondes + $time;

        $heureCouranteServiceMock = m::mock("TimeService")->shouldReceive("now")->andReturnUsing(
            function () {
                global $heureCourante;
                return $heureCourante;
            }
        )->getMock();

        App::singleton(
            "TimeService", function () use ($heureCouranteServiceMock) {
                return $heureCouranteServiceMock;
            }
        );
    }

    /**
     * @Then /^je devrais avoir en memcache une valeur de (\S+) pour l\'indicateur (\S+)$/
     */

    public function jeDevraisAvoirEnMemcacheUneValeurDePourLIndicateurDe($valeur, $indic) {
        $data = SinapsMemcache::get($indic);
        $valeurData = NULL;
        if ($data) {
            $valeurData = $data->valeur;
        }
        assertEquals($valeur, $valeurData);
    }


    /**
     *
     * @Given /^je devrais avoir les utilisateurs suivants:$/
     * @param \Behat\Gherkin\Node\TableNode $tab_utilisateur :
     * | login | nom | prenom |
     */
    public function jeDevraisAvoirLesUtilisateurs(TableNode $tab_utilisateur) {
//        var_dump($tab_utilisateur);
        foreach( $tab_utilisateur->getHash() as $user) {

        }
    }

    /**
     * @Given /^j'ai un code retour (\S+)$/
     */
    public function jAiUnCodeRetour($code) {

        assertEquals($code, $this->populateContext->getCodeRetour());
    }


    /**
     * @Given /^il y a (\d+) utilisateur connecté en base$/
     */
    public function IlyaNUtilisateurConnecteEnBase($nombre) {
        $users = Session::all();

        assertEquals($nombre, count($users));
    }



    /**
     * @Given /^j'ai les groupes/
     */
    public function jAiLesGroupes(TableNode $groupes) {

        $response = json_decode($this->populateContext->getLastResponse());

        assertTRUE($response->success);

        $count = 0;
        if(isset($response->payload->groupes)) {
            $listeRetournee = $response->payload->groupes;
        } else {
            $listeRetournee = $response->payload;
        }


        foreach( $groupes->getHash() as $groupe) {

            $ArrGroupeEnCours = array_shift($listeRetournee);
            $groupeEnCours = "";
            if (is_object($ArrGroupeEnCours)) {
                $groupeEnCours = $ArrGroupeEnCours->nom;
            }
            else {
                $groupeEnCours = $ArrGroupeEnCours;
            }
            assertEquals($groupe["groupes"], $groupeEnCours);

            $count++;
        }
    }

    /**
     * @Given /^j'obtiens une erreur de code (\d+)$/
     */
    public function jObtiensUneErreurDeCode($code) {
        $response = json_decode($this->populateContext->getLastResponse());

        assertFALSE($response->success);
        assertEquals($code, $response->code);
    }

    /**
     * @Given /^je n'ai pas d'erreur http$/
     */
    public function jeNAiPasDErreurHttp() {
        assertEquals(200, Response::$code);
    }

    /**
     * @Given /^j'ai une erreur http (\d+)$/
     */
    public function jAiUneErreurHttp($code) {
        assertEquals($code, Response::$code);
    }

    /**
     * Avance l'heure courante du délai indiqué
     */
    function moveTime($delai) {
        global $heureCourante;
        $heureCourante += $delai * 60;
    }

    /**
     * Avance l'heure courante du délai indiqué
     */
    function moveTimeSecondes($delai) {
        global $heureCourante;
        $heureCourante += $delai;
    }


    /******* API *************/

    public function getPayload() {

        $retour = json_decode($this->mock->toJSON());
        return json_decode($retour->payload);
    }

    /** ***************************************************************************
     * SIMULATION DE CONNEXION AVEC L'API
     *****************************************************************************/

    /**
     * @Given /^je demande la mise à jour via (\S+)$$/
     */
    public function jeDemandeLaMAJ($fichier) {

        // On modifie la sortie du log
        SinapsApp::$config["log.api.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.api.niveau"] = '0';
        SinapsApp::$config["log.api.formats"] = "TimeLogFormat,BaseLogFormat";

        SinapsApp::registerLogger("ApiLogger", "api");
        $logger = SinapsApp::make("ApiLogger");

        // On simule un retour webn c'est le fichier qui sera renvoyé
        $this->mock = MockedRestClient::getInstance();
        $this->mock->callFakeFootballApi($fichier);

        $this->apiController = new ApiFootballDataController();
        $this->apiController->miseAJourMatchDansLHeure();

        // On ferme le mock
        $this->mock->close();

        var_dump($this->returnAllLevelOfLog($logger));

    }

    /**
     * @Given /^je n\'ai pas de changement$/
     */
    public function aucunChangement() {

         $retour = $this->getPayload();
         assertEquals($retour->fixture->_links->competition->href, "http://api.football-data.org/v1/competitions/467");
         $this->mock->close();
    }

    /**
     * @Given /^le match d'id (\d+) n'a pas de score$/
     */
    public function matchNAPasDeScore($id) {

        $match = Match::find($id);
        assertNotNull($match);

        assertEquals(NULL, $match->score_dom, "Le score de l'équipe domicile n'est pas correct");
        assertEquals(NULL, $match->score_ext, "Le score de l'équipe extérieure n'est pas correct");
    }

    /**
     * @Given /^le match d'id (\d+) a le score (\d+)-(\d+)$/
     */
    public function matchALeScore($id, $scoreDom, $scoreExt) {

        $match = Match::find($id);
        assertNotNull($match);

        assertEquals($scoreDom, $match->score_dom, "Le score de l'équipe domicile n'est pas correct");
        assertEquals($scoreExt, $match->score_ext, "Le score de l'équipe extérieure n'est pas correct");
    }

    /**
     * @Given /^le match d'id (\d+) a le statut (\S+)$/
     */
    public function matchALeStatut($id, $etat) {

        $match = Match::find($id);
        assertNotNull($match);

        $etat = Etat::where("libelle", $etat)->first();
        assertNotNull($etat);

        assertEquals($etat->id, $match->etat_id, "Le statut du match n'est pas correct");
    }


    /********** MAJ PHASE FINALE *********************/

    /**
     * @Given /^la phase en cours est la phase de groupe$/
     */
    public function jeRecupereLaPhaseEnCoursGroupe() {

        // On modifie la sortie du log
        SinapsApp::$config["log.api.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.api.niveau"] = '0';
        SinapsApp::$config["log.api.formats"] = "TimeLogFormat,BaseLogFormat";

        SinapsApp::registerLogger("ApiLogger", "api");
        $logger = SinapsApp::make("ApiLogger");

        // On simule un retour webn c'est le fichier qui sera renvoyé
        $this->mock = MockedRestClient::getInstance();
        $this->mock->callFakeFootballApi("match_day_1");

        // On invoque la méthode (comme si on exécutait en ligne de commande le script)
        // Initialisation du controller
        $this->apiController = new ApiFootballDataController();
        $this->apiController->setPhaseEnCours();

        $this->phaseEnCours = $this->apiController->phaseEnCours;

        // On ferme le mock
        $this->mock->close();
    }

    /**
     * @Given /^la phase en cours est les phases finales$/
     */
    public function jeRecupereLaPhaseEnCoursPF() {

        // On modifie la sortie du log
        SinapsApp::$config["log.api.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.api.niveau"] = '0';
        SinapsApp::$config["log.api.formats"] = "TimeLogFormat,BaseLogFormat";

        SinapsApp::registerLogger("ApiLogger", "api");
        $logger = SinapsApp::make("ApiLogger");

        // On simule un retour webn c'est le fichier qui sera renvoyé
        $this->mock = MockedRestClient::getInstance();
        $this->mock->callFakeFootballApi("match_day_4");

        // On invoque la méthode (comme si on exécutait en ligne de commande le script)
        // Initialisation du controller
        $this->apiController = new ApiFootballDataController();
        $this->apiController->setPhaseEnCours();

        $this->phaseEnCours = $this->apiController->phaseEnCours;

        // On ferme le mock
        $this->mock->close();
    }

    /**
     * @Given /^la phase en cours a la valeur (\d+)$/
     */
    public function laPhaseEnCoursALaValeur($valeur) {
        assertEquals($this->apiController->phaseEnCours, $valeur);
    }

    /**
     * @Given /^je demande la mise à jour des phases finales via (\S+)$/
     */
    public function jeDemandeLaMAJPhaseFinale($fichier) {

        // On modifie la sortie du log
        SinapsApp::$config["log.api.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.api.niveau"] = '0';
        SinapsApp::$config["log.api.formats"] = "TimeLogFormat,BaseLogFormat";

        SinapsApp::registerLogger("ApiLogger", "api");
        $logger = SinapsApp::make("ApiLogger");

        // On simule un retour webn c'est le fichier qui sera renvoyé
        $this->mock = MockedRestClient::getInstance();
        $this->mock->callFakeFootballApi($fichier);

        // On invoque la méthode (comme si on exécutait en ligne de commande le script)
        // Initialisation du controller
        $this->apiController = new ApiFootballDataController();
        $this->apiController->phaseEnCours = $this->phaseEnCours;
        $this->apiController->majPhaseFinale();

        // On ferme le mock
        $this->mock->close();
    }

    /**
     * @Given /^le match d'id (\d+) n'est pas initialisé$/
     */
    public function matchNEstPasInitialise($id) {

        $match = Match::find($id);
        assertNotNull($match);

        assertEquals(NULL, $match->equipe_id_dom, "L'équipe domicile n'est pas correcte");
        assertEquals(NULL, $match->equipe_id_ext, "L'équipe extérieure n'est pas correcte");
    }

    /**
     * @Given /^le match d'id (\d+) se joue entre <(.*)> et <(.*)>$/
     */
    public function matchSeJoueEntre($id, $eqDom, $eqExt) {

        $match = Match::find($id);
        assertNotNull($match);

        $eqDomObj = Equipe::where("pays", $eqDom)->first();
        assertNotNull($eqDomObj);

        $eqExtObj = Equipe::where("pays", $eqExt)->first();
        assertNotNull($eqExtObj);

        assertEquals($eqDomObj->id, $match->equipe_id_dom, "L'équipe domicile n'est pas correcte");
        assertEquals($eqExtObj->id, $match->equipe_id_ext, "L'équipe extérieure n'est pas correcte");
    }

    /** LOG **/

        /**
     * @Given /^le log contient (.*)$/
     */
    public function leLogDeLApiContient($regexp) {
        $logger = SinapsApp::make("ApiLogger");
        $regexp = preg_quote($regexp, '/');

        assertGreaterThan(0, count(
                                preg_grep("/{$regexp}/",
                                          $this->returnAllLevelOfLog($logger))),
                            "La chaîne recherchée n'a pas été trouvée");
    }

    /**
     * @Given /^le log ne contient pas (.*)$/
     */
    public function leLogDeLApiNeContientPas($regexp) {
        $logger = SinapsApp::make("ApiLogger");
        $regexp = preg_quote($regexp, '/');
        assertEquals(0, count(
                                preg_grep("/{$regexp}/",
                                          $this->returnAllLevelOfLog($logger))),
                            "La chaîne recherchée n'a pas été trouvée");
    }

    function returnAllLevelOfLog($logger) {
        $log = array_merge(
            $logger->dump("error"),
            $logger->dump("info"),
            $logger->dump("debug"),
            $logger->dump("critical"),
            $logger->dump("warning")
        );

        return $log;
    }
}
