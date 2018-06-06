  <?php
/**
 * Tests sur les groupes d'utilisateurs.
 *
 * PHP version 5
 *
 * @author MSN Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 */

use \Mockery as m;

$HOME = __DIR__ . "/../../../WEB/";
require_once $HOME . "/ressource/php/Autoload.php";
require_once $HOME . "/../TEST/test_commun/Utils.php";
require_once $HOME . "/controller/ParisController.php";
require_once $HOME . "/controller/LoginController.php";

//class GroupeControllerTest extends \PHPUnit_Framework_TestCase {
class ParisControllerTest extends SinapsTestCase {

    public function setUp() {
        App::initialise();

        Utils::initFakeServeur();
        Utils::initFakeMailService();

        SinapsApp::singleton("Logger", function() { return new LogsNG();});

        // Utilisation d'un FakeMail
        SinapsApp::singleton("MailService", function() { return new FakeMailService();});


        SinapsApp::singleton("LoginService", function() { return new LoginService();});
        SinapsApp::singleton("JsonService", function() { return new JsonService();});
        SinapsApp::singleton("SystemService", function() { return new SystemService();});
        SinapsApp::singleton("ParisService", function() { return new ParisService();});
        SinapsApp::register("DateService");
        SinapsApp::register("TimeService");
        SinapsApp::register("RestClientService");
        SinapsApp::register("UtilisateurService");

        App::filter("authentification", "AuthentificationFilter");
        $this->loginController = new LoginController();

        Input::set('login', 'admin');
        Input::set('password', 'admin');
        $this->loginController->postAuth();

        $utilisateur = Utilisateur::find(1);
        SinapsApp::setUtilisateurCourant($utilisateur);

        $this->parisController = new ParisController();
    }

    // Est appelé en fin de test
    public function tearDown() {
        Utils::truncateAll();
    }

// FONCTIONS QUI VONT ETRE TESTEE

    /**
     * reçoit un pari vide
     *
     * Exemple de structure que doit recevoir le controller
     * array(1) {
          [0]=>
          object(stdClass)#10 (3) {
            ["id"]=>
            int(165069)
            ["scoreDom"]=>
            string(1) "1"
            ["scoreExt"]=>
            string(1) "0"
          }
        }
    */

    public function testSauvegarderParisVide() {
        $listeParis = Input::set('listParis', array());

        // Sauvegarde du pari
        $retour = json_decode($this->parisController->sauvegarderParis());

        $this->assertEquals(TRUE, $retour->success, "testSauvegarderParis() GET -> non succès");
        $this->assertEquals("", $retour->code, "testSauvegarderParis() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "testSauvegarderParis() GET -> payload");
    }

    public function testSauvegarderParisNonVide() {
        $objPari = new StdClass();
        $objPari->id = 165069;
        $objPari->scoreDom = 1;
        $objPari->scoreExt = 0;

        // On simule l'envoi de la variable
        Input::set('listParis', array($objPari));

        // Sauvegarde du pari
        $retour = json_decode($this->parisController->sauvegarderParis());

        $this->assertEquals(TRUE, $retour->success, "testSauvegarderParis() GET -> non succès");
        $this->assertEquals("", $retour->code, "testSauvegarderParis() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "testSauvegarderParis() GET -> payload");

        // On récupère le pari qui normalement a du etre enregistré
        $monPari = Paris::where("match_id", 165069)
                        ->where("utilisateur_id", 1)
                        ->first();

        // On teste les valeurs
        $this->assertEquals($monPari->score_dom, 1,  "testSauvegarderParisNonVide() score_dom incorrect");
        $this->assertEquals($monPari->score_ext, 0,  "testSauvegarderParisNonVide() score_ext incorrect");
        $this->assertEquals($monPari->match_id, 165069,  "testSauvegarderParisNonVide() match_id incorrect");
        $this->assertEquals($monPari->utilisateur_id, 1,  "testSauvegarderParisNonVide() utilisateur_id incorrect");
    }

    public function testSauvegarderParisPlusieurs() {

        $mesParis = array();

        // On cré deux objets paris
        $objPari = new StdClass();
        $objPari->id = 165069;
        $objPari->scoreDom = 1;
        $objPari->scoreExt = 0;

        // On push
        array_push($mesParis, $objPari);

        $objPari = new StdClass();
        $objPari->id = 165091;
        $objPari->scoreDom = 3;
        $objPari->scoreExt = 5;

        // On push
        array_push($mesParis, $objPari);

        // On simule l'envoi de la variable
        Input::set('listParis', $mesParis);

        // Sauvegarde du pari
        $retour = json_decode($this->parisController->sauvegarderParis());

        $this->assertEquals(TRUE, $retour->success, "testSauvegarderParis() GET -> non succès");
        $this->assertEquals("", $retour->code, "testSauvegarderParis() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "testSauvegarderParis() GET -> payload");

        // On récupère le pari qui normalement a du etre enregistré
        $monPari = Paris::where("match_id", 165069)
                        ->where("utilisateur_id", 1)
                        ->first();

        // On teste les valeurs
        $this->assertEquals($monPari->score_dom, 1,  "testSauvegarderParisNonVide() score_dom incorrect");
        $this->assertEquals($monPari->score_ext, 0,  "testSauvegarderParisNonVide() score_ext incorrect");
        $this->assertEquals($monPari->match_id, 165069,  "testSauvegarderParisNonVide() match_id incorrect");
        $this->assertEquals($monPari->utilisateur_id, 1,  "testSauvegarderParisNonVide() utilisateur_id incorrect");

        // test deuxieme paris
        $monPari = Paris::where("match_id", 165091)
                        ->where("utilisateur_id", 1)
                        ->first();

        $this->assertEquals($monPari->score_dom, 3,  "testSauvegarderParisNonVide() score_dom incorrect");
        $this->assertEquals($monPari->score_ext, 5,  "testSauvegarderParisNonVide() score_ext incorrect");
        $this->assertEquals($monPari->match_id, 165091,  "testSauvegarderParisNonVide() match_id incorrect");
        $this->assertEquals($monPari->utilisateur_id, 1,  "testSauvegarderParisNonVide() utilisateur_id incorrect");
    }


    public function testSauvegarderParisDejaEnBase() {

        // On cré l'objet en base
        $pari = new Paris();
        $pari->score_dom = 3;
        $pari->score_ext = 4;
        $pari->match_id = 165069;
        $pari->utilisateur_id = 1;
        $pari->save();

        $objPari = new StdClass();
        $objPari->id = 165069;
        $objPari->scoreDom = 1;
        $objPari->scoreExt = 0;

        // On simule l'envoi de la variable
        Input::set('listParis', array($objPari));

        // Sauvegarde du pari
        $retour = json_decode($this->parisController->sauvegarderParis());

        $this->assertEquals(TRUE, $retour->success, "testSauvegarderParis() GET -> non succès");
        $this->assertEquals("", $retour->code, "testSauvegarderParis() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "testSauvegarderParis() GET -> payload");

        // On récupère le pari qui normalement a du etre enregistré
        $monPari = Paris::where("match_id", 165069)
                        ->where("utilisateur_id", 1)
                        ->first();

        // On teste les valeurs
        $this->assertEquals($monPari->score_dom, 1,  "testSauvegarderParisNonVide() score_dom incorrect");
        $this->assertEquals($monPari->score_ext, 0,  "testSauvegarderParisNonVide() score_ext incorrect");
        $this->assertEquals($monPari->match_id, 165069,  "testSauvegarderParisNonVide() match_id incorrect");
        $this->assertEquals($monPari->utilisateur_id, 1,  "testSauvegarderParisNonVide() utilisateur_id incorrect");
    }



}
