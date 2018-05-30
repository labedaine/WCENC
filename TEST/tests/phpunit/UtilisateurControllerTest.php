 <?php

/**
 * Tests sur les utilisateurs.
 *
 * PHP version 5
 *
 * @author MSN Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 */

use \Mockery as m;

$HOME = __DIR__ . "/../../../WEB";

require_once $HOME . "/ressource/php/Autoload.php";
require_once $HOME . "/../TEST/test_commun/Utils.php";
require_once $HOME . "/controller/UtilisateurController.php";
require_once $HOME . "/service/UtilisateurService.php";

//class UtilisateurControllerTest extends \PHPUnit_Framework_TestCase {
class UtilisateurControllerTest extends SinapsTestCase {

    public function setUp() {
        App::initialise("MySQL");

        Utils::initFakeServeur("MySQL");

        SinapsApp::singleton("Logger", function() { return new LogsNG();});
        SinapsApp::singleton("JsonService", function() { return new JsonService();});
        SinapsApp::singleton("JqGridService", function() { return new JqGridService();});
        SinapsApp::singleton("FileService", function() { return new FileService();});
        SinapsApp::singleton("SystemService", function() { return new SystemService();});
        SinapsApp::singleton("DroitsService", function() { return new DroitsService();});
        SinapsApp::singleton("MailService", function() { return new FakeMailService();});

        SinapsApp::register("DateService");
        SinapsApp::register("TimeService");
        SinapsApp::register("RestClientService");
        SinapsApp::register("LoginService");
        SinapsApp::register("UtilisateurService");

        SinapsApp::$config["log.deploiement.writers"] = "MemoryLogWriter,ConsoleLogWriter";
        SinapsApp::$config["log.deploiement.formats"] = "BaseLogFormat";

        /**
         *  Propriété vers le controller à tester
         */

        $this->utilisateurController = new UtilisateurController();

        Utils::populate();

        App::filter("authentification", "AuthentificationFilter");
        $this->loginController = new LoginController();

        Input::set('login', 'admin');
        Input::set('password', 'admin');
        $this->loginController->postAuthRestitution();

    }


    public function tearDown() {
        Utils::truncateAll();
    }

    public function ajouterUtilisateurErreur1() {
//        var_dump('ajouterUtilisateurErreur1');
//        $this->utilisateurController->xxx();
    }


    // Liste des Utilisateurs
    public function testGetUtilisateursListe() {
        $retour = json_decode($this->utilisateurController->getUtilisateursListe());

		// tests de structure de l'objet retourné
        $this->assertObjectHasAttribute("records", $retour, "testGetUtilisateursListe -> records absent");
        $this->assertObjectHasAttribute("page", $retour, "testGetUtilisateursListe -> page absent");
        $this->assertObjectHasAttribute("total", $retour, "testGetUtilisateursListe -> total absent");
        $this->assertObjectHasAttribute("rows", $retour, "testGetUtilisateursListe -> rows absent");

        // tests de l'objet retourné
        $this->assertCount(2, $retour->rows, "testGetUtilisateursListe() -> nb profils != 2 ");

        // tests du contenu
        $this->assertEquals("ADMIN", $retour->rows[0]->cell[5], "testGetAllProfils() -> retour->rows[0]->cell[5] != 'ADMIN'");

    }


}
