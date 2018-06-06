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

require_once $rootDir."/ressource/php/services/TimeService.php";
require_once $rootDir.'/ressource/php/framework/Log.php';

$heureCourante = 0;

/**
 * Features context.
 */

class PopulateSubContext implements Context
{
    private $now = FALSE;

    // Pour les recherches dans les fichiers
    static $fileBuffer;

    // Pour le sign-in
    private $token;

    // Pour le code retour du login
    private $codeRetour;
    private $utilisateurConnecte = NULL;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct() {
    }

    /** @BeforeScenario */
    public function before($event) {

        // Récupération du père
        $environment = $event->getEnvironment();
        $this->mainContext = $environment->getContext('FeatureContext');
    }

    public function getToken() {
        return $this->token;
    }

    /** *******************************************************
     * Générique
     *
     * Les phrases présentes ici sont très générique et ont pour
     * but de permettre de s'abstraire de la logique interne du
     * produit
     ******************************************************** */

    /* ********************************************************
     * GESTION DES UTILISATEURS - LOGIN
     ********************************************************** */

    /**
     * @Given /^je cré les utilisateurs:$/
     */
    public function jeCreLesUtilisateurs(TableNode $users) {

        // Création des utilisateurs
        foreach( $users->getHash() as $unUser) {
            $objUtilisateur = Utilisateur::where("nom", $unUser['nom'])->first();
            if ($objUtilisateur === NULL) {
                $objUtilisateur = new Utilisateur();
            }
            $objUtilisateur->nom = $unUser['nom'];
            $objUtilisateur->prenom = $unUser['nom'];
            $objUtilisateur->login = $unUser['nom'];
            $objUtilisateur->email = $unUser['nom']."@betfip.fr";
            $objUtilisateur->password =  md5($unUser['password']);
            $objUtilisateur->promotion = 0;
            $objUtilisateur->points = 0;
            $objUtilisateur->isactif = $unUser['isActif'];
            $objUtilisateur->isadmin = $unUser['isAdmin'];
            $objUtilisateur->save();
        }
    }

    /**
     * @Given /^exit$/
     */
    public function exitNow() {
        exit(1);
    }

    /** ***************************************************************************
     * UTILISATEURS
     *
     *
     *
     *****************************************************************************/

    /**
     * @Given /^je me connecte avec (\S+) \/ (\S+)$/
     */
    public function jeMeConnecteAvec($login, $passwd) {
        $mock = MockedRestClient::getInstance();

        $controller = new LoginController();
        Input::set("login", $login);
        Input::set("password", $passwd);

        $json = $controller->postAuth($login, $passwd);

        if ($json) {
            $result = json_decode($json);
            $this->codeRetour = $result->code;
            if ($result->success) {
                // Mise à jour du user connecté dans la classe SinapsApp
                $this->mainContext->setUtilisateurCourant(Utilisateur::where('login', $login)->first());
            }
        }
        $this->token = $this->mainContext->getFromCookie("token");
        $mock->close();
    }

    public function getCodeRetour() {
        return $this->codeRetour;
    }

    public function getLastResponse() {
        return $this->lastResponse;
    }

    /**
     * @Given /^je me déconnecte$/
     */
    public function jeMeDeconnecte() {
        $this->mainContext->setInCookie("token", $this->token);
        $controller = new LoginController();
        $controller->deleteAuth();
    }

    /**
     * @Given /^je demande la liste de mes paris$/
     */

    public function jeDemandeLaListeDeMesParis() {

        $this->lastResponse = NULL;
        $this->code = NULL;

        if($this->token) {
            $this->mainContext->setInCookie("token", $this->token);
        }

        $parisController = new ParisController();
        $this->lastResponse = $parisController->invoke("getListeMatch");
        $this->code = Response::$code;
    }

    /**
     * @Given /^j'ai un access denied$/
     */
    public function jaiUnAccesDenied() {
        assertContains("Access denied", $this->lastResponse);
    }


    /**
     * @Given /^je demande la vue administrateur$/
     */
    public function jeDemandeLaVueAdministrateur() {

        $this->lastResponse = NULL;
        $this->code = NULL;

        if($this->token) {
            $this->mainContext->setInCookie("token", $this->token);
        }

        $administrationController = new AdministrationController();
        $this->lastResponse = $administrationController->invoke("getUtilisateursListe");
        $this->code = Response::$code;
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
