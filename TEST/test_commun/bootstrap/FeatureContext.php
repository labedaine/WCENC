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
    private $moteur = FALSE;
    private $moteurModele = NULL;
    private $now = FALSE;
    private $ipCount;
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

        //SinapsApp::registerLogger("DemandeCollecteurLogger", "demande");
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
        $time = mktime(2018, 06, 14, 0, 0, 0);
        $heureCourante = ($heures - 1) * 3600 + $minutes * 60 + $secondes + $time;
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
     * @Given /^je demande à afficher les nouvelles alertes$/
     */
    public function jeDemandeAAfficherLesNouvellesAlertes() {

        Cookie::set("token", $this->populateContext->getToken());
        $controller = new AlerteController();

        // On simule le passage postData de mesApplication: [listeDesApplications] et du _search à false
        Input::set("mesApplications", array(1));
        Input::set('_search','false');

        $_REQUEST['sidx'] = 'date';
        $_REQUEST['sord'] = 'desc';

        $this->response = $controller->invoke("getListeNouvelles");
        $this->code = Response::$code;
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
}
