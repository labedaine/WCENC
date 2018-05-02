  <?php
/**
 * Tests sur les groupes d'utilisateurs.
 *
 * PHP version 5
 *
 * @author MSN Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 */

use \Mockery as m;

$HOME = __DIR__ . "/../../../../apps";

require_once $HOME . "/commun/php/Autoload.php";
require_once $HOME . "/../tests/test_commun/Utils.php";
require_once $HOME . "/restitution/services/controllers/GroupeController.php";
require_once $HOME . "/restitution/services/controllers/TableauxDeBordController.php";
require_once $HOME . "/restitution/services/services/GroupeService.php";
require_once $HOME . "/restitution/services/services/DroitsService.php";
require_once $HOME."/restitution/services/services/IndicateurGrapheService.php";
require_once $HOME . "/commun/php/controllers/LoginController.php";

//class GroupeControllerTest extends \PHPUnit_Framework_TestCase {
class GroupeControllerTest extends SinapsTestCase {

    public function setUp() {
        App::initialise();

        Utils::initFakeServeur("MySQL");
        Utils::initFakeMailService();

        SinapsApp::singleton("Logger", function() { return new LogsNG();});
        SinapsApp::singleton("DroitsService", function() { return new DroitsService();});
        SinapsApp::singleton("LoginService", function() { return new LoginService();});
        SinapsApp::singleton("JsonService", function() { return new JsonService();});
        SinapsApp::singleton("JqGridService", function() { return new JqGridService();});
        SinapsApp::singleton("FileService", function() { return new FileService();});
        SinapsApp::singleton("SystemService", function() { return new SystemService();});
        SinapsApp::singleton("MailService", function() { return new FakeMailService();});
        SinapsApp::register("DateService");
        SinapsApp::register("TimeService");
        SinapsApp::register("RestClientService");
        SinapsApp::register("GroupeService");
        SinapsApp::register("UtilisateurService");
        SinapsApp::registerSingleton("IndicateurGrapheService");
        SinapsApp::registerSingleton("DroitsService");

        SinapsApp::$config["log.deploiement.writers"] = "MemoryLogWriter,ConsoleLogWriter";
        SinapsApp::$config["log.deploiement.formats"] = "BaseLogFormat";

        Utils::populate();

        App::filter("authentification", "AuthentificationFilter");
        $this->loginController = new LoginController();

        Input::set('login', 'admin');
        Input::set('password', 'admin');
        $this->loginController->postAuthRestitution();

        $utilisateur = Utilisateur::find(1);
        SinapsApp::setUtilisateurCourant($utilisateur);

        $this->groupeController = new GroupeController();
    }


    public function tearDown() {
        Utils::truncateAll();
    }

// FONCTIONS QUI VONT ETRE TESTEE

    public function testTousLesTests() {
        
        $this->gestionGroupeAdd();
        $this->gestionGroupeErreur();
        $this->gestionGroupeMod();
        $this->gestionGroupeDel();
    }

    public function testControleAvantSuppression() {
        $id = Input::set('id', 1);

        // On cré un nouveau groupe TOTO qui aura les droits sur Sinaps  - ADMIN sur Sinaps et TELEIR
        $this->gestionGroupeAdd();

        
        // on ajoute les droits sur TELEIR pour TOTO et ADMIN
        $appDuGroupe = new ApplicationDuGroupe();
        $appDuGroupe->Profil_id = 2;        // Profil G2A
        $appDuGroupe->Application_id = 1;   // Sinaps
        $groupe = Groupe::where('nom', 'NomTOTO')->first();
        if ($groupe !== NULL) {
            $appDuGroupe->Groupe_id = $groupe->id;
        }
        $appDuGroupe->nomGroupe = "NomTOTO";
        $appDuGroupe->save();

        $userDuGroupe = new UtilisateurDuGroupe();
        $userDuGroupe->Utilisateur_id = 2;  // sinaps
        $userDuGroupe->Groupe_id = 2;       // NomTOTO
        $userDuGroupe->save();

        $this->controlePremier();

        $appDuGroupe = new ApplicationDuGroupe();
        $appDuGroupe->Profil_id = 1;        // Profil ADMIN
        $appDuGroupe->Application_id = 1;   // Sinaps
        $appDuGroupe->Groupe_id = 1;
        $appDuGroupe->nomGroupe = "ADMIN";
        $appDuGroupe->save();

        $appDuGroupe = new ApplicationDuGroupe();
        $appDuGroupe->Profil_id = 2;        // Profil G2A
        $appDuGroupe->Application_id = 2;   // TeleIR
        $appDuGroupe->Groupe_id = 1;
        $appDuGroupe->nomGroupe = "ADMIN";
        $appDuGroupe->save();

        $userDuGroupe = new UtilisateurDuGroupe();
        $userDuGroupe->Utilisateur_id = 2;  // sinaps
        $userDuGroupe->Groupe_id = 1;       // ADMIN
        $userDuGroupe->save();

        $this->controleSecond();

        // On ajoute un TDB de groupe
        SinapsApp::$config = array( "configTB_composant_boxWidth" => 75,
                                    "configTB_composant_boxHeight" => 60,
                                    "configTB_composant_numCol" => 10,
                                    "configTB_composant_numRow" => 4,
                                    "configTB_graph_boxWidth" => 390,
                                    "configTB_graph_boxHeight" => 200,
                                    "configTB_graph_numRow" => 2,
                                    "configTB_graph_numCol" => 2,
                                    "configTB_refreshTime" => 60000,
                                    "graph_maxTimeInMinutesGraphDrawLine" => 360,
                                    "graph_maxPoints" => 1000
                             );

        $tdb = TableauDeBord::find(1);
        $tdb->Groupe_id = 1;
        $tdb->Application_id = NULL;
        $tdb->save();

        $this->controleTroisieme();

        // Maintenant on supprime ADMIN et on voit ce qui se passe
        $oper = Input::set('oper', 'del');
        $id = Input::set('id', '1');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());

        $this->controleDernier();
    }

// FONCTIONS SECONDAIRES

    // Premier test avec seulement 1 utilisateur
    private function controlePremier() {

        $id = Input::set('id', 1);

        $liste = json_decode($this->groupeController->controleAvantSuppression());

        // On teste le retour de add
        $this->assertEquals(TRUE, $liste->success, "controlePremier() GET -> non succès");
        $this->assertEquals("", $liste->code, "controlePremier() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $liste, "controlePremier() GET -> payload");

        $payload = $liste->payload;

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("nom", $payload, "controlePremier() -> nom absent");
        $this->assertObjectHasAttribute("tdbs", $payload, "controlePremier() -> tdbs absent");
        $this->assertObjectHasAttribute("acces", $payload, "controlePremier() -> acces absent");
        $this->assertObjectHasAttribute("utilisateurs", $payload, "controlePremier() -> utilisateurs absent");

        // tests du contenu
        $this->assertEquals("ADMIN", $payload->nom, "controlePremier() -> nom != ADMIN");
        $this->assertContains("Aucun tableau de bord n'est accessible pour ce groupe.", $payload->tdbs, "controlePremier() -> tdbs non vide");
        $this->assertContains("Aucune application n'est accessible par ce groupe.", $payload->acces, "controlePremier() -> acces non vide");
        $this->assertCount(1, $payload->utilisateurs, "controlePremier() -> nb utilisateurs != 1 ");
        $this->assertContains("admin admin", $payload->utilisateurs, "controlePremier() -> utilisateurs ne contient 'admin admin' ");
    }

    // Second test avec seulement 2 utilisateurs et 2 application
    private function controleSecond() {

        $id = Input::set('id', 1);

        $liste = json_decode($this->groupeController->controleAvantSuppression());

        // On teste le retour de add
        $this->assertEquals(TRUE, $liste->success, "controleSecond() GET -> non succès");
        $this->assertEquals("", $liste->code, "controleSecond() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $liste, "controleSecond() GET -> payload");

        $payload = $liste->payload;

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("nom", $payload, "controleSecond() -> nom absent");
        $this->assertObjectHasAttribute("tdbs", $payload, "controleSecond() -> tdbs absent");
        $this->assertObjectHasAttribute("acces", $payload, "controleSecond() -> acces absent");
        $this->assertObjectHasAttribute("utilisateurs", $payload, "controleSecond() -> utilisateurs absent");

        // tests du contenu
        $this->assertEquals("ADMIN", $payload->nom, "controleSecond() -> nom != ADMIN");
        $this->assertContains("Aucun tableau de bord n'est accessible pour ce groupe.", $payload->tdbs, "controleSecond() -> tdbs non vide");

        $this->assertCount(2, $payload->acces, "controleSecond() -> nb acces != 2 ");
        $this->assertContains("Sinaps", $payload->acces, "controleSecond() -> acces ne contient 'Sinaps' ");
        $this->assertContains("TeleIR", $payload->acces, "controleSecond() -> acces ne contient 'TeleIR' ");

        $this->assertCount(2, $payload->utilisateurs, "controleSecond() -> nb utilisateur != 1 ");
        $this->assertContains("admin admin", $payload->utilisateurs, "controleSecond() -> utilisateurs ne contient 'admin admin' ");
        $this->assertContains("sinaps", $payload->utilisateurs, "controleSecond() -> utilisateurs ne contient 'sinaps' ");

    }

    // Troisieme test avec seulement 2 utilisateur et 2 application et 1 tdb
    private function controleTroisieme() {

        $id = Input::set('id', 1);

        $liste = json_decode($this->groupeController->controleAvantSuppression());

        // On teste le retour de add
        $this->assertEquals(TRUE, $liste->success, "controleTroisieme() GET -> non succès");
        $this->assertEquals("", $liste->code, "controleTroisieme() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $liste, "controleTroisieme() GET -> payload");

        $payload = $liste->payload;

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("nom", $payload, "controleTroisieme() -> nom absent");
        $this->assertObjectHasAttribute("tdbs", $payload, "controleTroisieme() -> tdbs absent");
        $this->assertObjectHasAttribute("acces", $payload, "controleTroisieme() -> acces absent");
        $this->assertObjectHasAttribute("utilisateurs", $payload, "controleTroisieme() -> utilisateurs absent");

        // tests du contenu
        $this->assertEquals("ADMIN", $payload->nom, "controleTroisieme() -> nom != ADMIN");
        $this->assertContains("Sinaps 001", $payload->tdbs, "controleTroisieme() -> tdbs ne contient pas 'Sinaps 001'");

        $this->assertCount(2, $payload->acces, "controleTroisieme() -> nb acces != 2 ");
        $this->assertContains("Sinaps", $payload->acces, "controleTroisieme() -> acces ne contient 'Sinaps' ");
        $this->assertContains("TeleIR", $payload->acces, "controleTroisieme() -> acces ne contient 'TeleIR' ");

        $this->assertCount(2, $payload->utilisateurs, "controleTroisieme() -> nb utilisateur != 1 ");
        $this->assertContains("admin admin", $payload->utilisateurs, "controleTroisieme() -> utilisateurs ne contient 'admin admin' ");
        $this->assertContains("sinaps", $payload->utilisateurs, "controleTroisieme() -> utilisateurs ne contient 'sinaps' ");

    }

    // Dernier test après suppression groupe ADMIN
    private function controleDernier() {

        // Il ne doit pas rester de relation entre le groupe ADMIN et TeleIR/Sinaps
        // Il ne doit pas rester d'utilisateurs dans le groupe ADMIN
        // Il ne doit pas rester de tdb de groupe pour ADMIN
        // Il ne doit pas rester de groupe ADMIN

        $id = Input::set('id', 1);

        $retour = json_decode($this->groupeController->controleAvantSuppression());
        // Erreur pas de groupe 1
        $this->assertEquals(FALSE, $retour->success, "controleDernier() GET -> non succès");
        $this->assertEquals("500", $retour->code, "controleDernier() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "controleDernier() GET -> payload");
        $this->assertEquals("Le groupe n'existe pas.", $retour->payload, "controleDernier() Mauvaise chaine de payload");

        // Il ne doit pas rester de tdb de groupe pour ADMIN
        $tdb = TableauDeBord::all();
        $this->assertCount(0, $tdb, "controleDernier() le tdb n'a pas été supprimé");

        // Il ne doit pas rester d'utilisateurs dans le groupe ADMIN
        $usersDuGroupe = UtilisateurDuGroupe::where('Groupe_id', 1)->get();
        $this->assertCount(0, $usersDuGroupe, "controleDernier() UtilisateurDuGroupe pour ADMIN n'a pas été supprimé");
        $usersDuGroupe = UtilisateurDuGroupe::where('Groupe_id', 2)->get();
        $this->assertCount(1, $usersDuGroupe, "controleDernier() UtilisateurDuGroupe pour NomTOTO a été supprimé");

        // Il ne doit pas rester de relation entre le groupe ADMIN et TeleIR/Sinaps
        $appDuGroupe = ApplicationDuGroupe::where('nomGroupe', "ADMIN")->get();
        $this->assertCount(0, $appDuGroupe, "controleDernier() ApplicationDuGroupe pour ADMIN n'a pas été supprimé");
        $appDuGroupe = ApplicationDuGroupe::where('nomGroupe', "NomTOTO")->get();
        $this->assertCount(1, $appDuGroupe, "controleDernier() ApplicationDuGroupe pour NomTOTO a été supprimé");
    }

    // Liste des Groupes - Ne sera pas exécuté seul
    private function getGroupesListe() {

        // chargement d'une liste de groupe
        $liste = json_decode($this->groupeController->getGroupesListe());

        // test de cohérence
        $this->assertCount($liste->records, $liste->rows, "getGroupesListe() -> liste->records différent de liste->rows");

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("records", $liste, "getGroupesListe() -> records absent");
        $this->assertObjectHasAttribute("page", $liste, "getGroupesListe() -> page absent");
        $this->assertObjectHasAttribute("total", $liste, "getGroupesListe() -> total absent");
        $this->assertObjectHasAttribute("rows", $liste, "getGroupesListe() -> rows absent");
        
        // tests du n_ième groupe de l'objet retourné
        $i = rand(1, count($liste->rows))-1;

        $this->assertObjectHasAttribute("id", $liste->rows[$i], "getGroupesListe() -> id absent");
        $this->assertObjectHasAttribute("cell", $liste->rows[$i], "getGroupesListe() -> cell absent");
        $this->assertEquals(10, count($liste->rows[$i]->cell), "getGroupesListe() -> nombre de données différents de 10 pour le ".($i+1)."° groupe");

        // tests de non nullité de l'id et du nom du groupe testé
        $this->assertNotNull($liste->rows[$i]->cell[0]);
        $this->assertNotEquals("", $liste->rows[$i]->cell[1], "getGroupesListe() -> nom absent pour le ".($i+1)."° groupe");
        $this->assertNotNull($liste->rows[$i]->cell[1], "getGroupesListe() -> nom = NULL pour le ".($i+1)."° groupe");

        return $liste;
    }

    // Ajout des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeAdd() {

        $oper = Input::set('oper', 'add');
        $id = Input::set('id', 'empty_');

        // Parametre du nouveau groupe
        $id = Input::set('nom', 'NomTOTO');
        $id = Input::set('groupeMail', 'groupeMailTOTO');
        $id = Input::set('groupeTelephone', 'groupeTelephoneTOTO');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());
        
        // On teste le retour de add
        $this->assertEquals(TRUE, $retour->success, "gestionGroupeAdd() GET -> non succès");
        $this->assertEquals("", $retour->code, "gestionGroupeAdd() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeAdd() GET -> payload");

        $liste = $this->getGroupesListe(TRUE);
        
        // test de cohérence
        $this->assertCount($liste->records, $liste->rows, "gestionGroupeAdd() -> liste->records différent de liste->rows");
        $this->assertEquals($liste->records, 6, "gestionGroupeAdd() -> liste->records différent de 2");

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("records", $liste, "gestionGroupeAdd() -> records absent");
        $this->assertObjectHasAttribute("page", $liste, "gestionGroupeAdd() -> page absent");
        $this->assertObjectHasAttribute("total", $liste, "gestionGroupeAdd() -> total absent");
        $this->assertObjectHasAttribute("rows", $liste, "gestionGroupeAdd() -> rows absent");

        // tests de celui que l'on a ajouté
        $celuiQuonAAjoute = $liste->rows[4];

        $this->assertObjectHasAttribute("id", $celuiQuonAAjoute, "gestionGroupeAdd() -> id absent");
        $this->assertObjectHasAttribute("cell", $celuiQuonAAjoute, "gestionGroupeAdd() -> cell absent");
        $this->assertEquals(10, count($celuiQuonAAjoute->cell), "gestionGroupeAdd() -> nombre de données différents de 10 pour celui qu'on a ajouté");

        // tests de non nullité de l'id et du nom du groupe testé
        $this->assertEquals(6, $celuiQuonAAjoute->cell[0], 'gestionGroupeAdd() id != 6');
        $this->assertEquals("NomTOTO", $celuiQuonAAjoute->cell[1], 'gestionGroupeAdd() id != NomTOTO');
        $this->assertEquals("groupeMailTOTO", $celuiQuonAAjoute->cell[2], 'gestionGroupeAdd() mail != groupeMailTOTO');
        $this->assertEquals("groupeTelephoneTOTO", $celuiQuonAAjoute->cell[3], 'gestionGroupeAdd() tel != groupeTelephoneTOTO');
    }



    // Modification des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeMod() {

        $oper = Input::set('oper', 'edit');
        $id = Input::set('id', '2');

        // Parametre du nouveau groupe
        $id = Input::set('nom', 'nomTITI');
        $id = Input::set('groupeMail', 'groupeMailTITI');
        $id = Input::set('groupeTelephone', 'groupeTelephoneTITI');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());

        // On teste le retour de add
        $this->assertEquals(TRUE, $retour->success, "gestionGroupeMod() GET -> non succès");
        $this->assertEquals("", $retour->code, "gestionGroupeMod() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeMod() GET -> payload");

        $liste = $this->getGroupesListe(TRUE);

        // test de cohérence
        $this->assertCount($liste->records, $liste->rows, "gestionGroupeMod() -> liste->records différent de liste->rows");
        $this->assertEquals($liste->records, 6, "gestionGroupeMod() -> liste->records différent de 6");

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("records", $liste, "gestionGroupeMod() -> records absent");
        $this->assertObjectHasAttribute("page", $liste, "gestionGroupeMod() -> page absent");
        $this->assertObjectHasAttribute("total", $liste, "gestionGroupeMod() -> total absent");
        $this->assertObjectHasAttribute("rows", $liste, "gestionGroupeMod() -> rows absent");

        // tests de celui que l'on a ajouté
        $celuiQuonAAjoute = $liste->rows[3];

        $this->assertObjectHasAttribute("id", $celuiQuonAAjoute, "gestionGroupeMod() -> id absent");
        $this->assertObjectHasAttribute("cell", $celuiQuonAAjoute, "gestionGroupeMod() -> cell absent");
        $this->assertEquals(10, count($celuiQuonAAjoute->cell), "gestionGroupeMod() -> nombre de données différents de 10 pour celui qu'on a ajouté");

        // tests de non nullité de l'id et du nom du groupe testé
        $this->assertEquals(2, $celuiQuonAAjoute->cell[0], 'gestionGroupeMod() id != 2');
        $this->assertEquals("nomTITI", $celuiQuonAAjoute->cell[1], 'gestionGroupeMod() id != nomTITI');
        $this->assertEquals("groupeMailTITI", $celuiQuonAAjoute->cell[2], 'gestionGroupeMod() mail != groupeMailTITI');
        $this->assertEquals("groupeTelephoneTITI", $celuiQuonAAjoute->cell[3], 'gestionGroupeMod() tel != groupeTelephoneTITI');
    }

    // Ajout des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeAddErreur() {

        // Gestion doublon
        $oper = Input::set('oper', 'add');
        $id = Input::set('id', 'empty_');

        // Parametre du nouveau groupe
        $id = Input::set('nom', 'nomTOTO');
        $id = Input::set('groupeMail', 'groupeMailTOTO');
        $id = Input::set('groupeTelephone', 'groupeTelephoneTOTO');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());

        // On teste le retour de add
        $this->assertEquals(FALSE, $retour->success, "gestionGroupeAddDoublon() GET -> non succès");
        $this->assertEquals("500", $retour->code, "gestionGroupeAddDoublon() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeAddDoublon() GET -> payload");
        $this->assertEquals("Action impossible car le groupe nomTOTO existe déjà (NomTOTO).", $retour->payload, "gestionGroupeAddDoublon() Mauvaise chaine de payload");
    }

    // Ajout des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeModErreur() {

        // Gestion doublon
        $oper = Input::set('oper', 'edit');
        $id = Input::set('id', '2');

        // Parametre du nouveau groupe
        $id = Input::set('nom', 'aDmIn');
        $id = Input::set('groupeMail', 'groupeMailTOTO');
        $id = Input::set('groupeTelephone', 'groupeTelephoneTOTO');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());

        // On teste le retour de add
        $this->assertEquals(FALSE, $retour->success, "gestionGroupeModErreur() GET -> non succès");
        $this->assertEquals("500", $retour->code, "gestionGroupeModErreur() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeModErreur() GET -> payload");
        $this->assertEquals("Action impossible car le groupe aDmIn existe déjà (ADMIN).", $retour->payload, "gestionGroupeModErreur() Mauvaise chaine de payload");

        // Gestion id existe pas
        $oper = Input::set('oper', 'edit');
        $id = Input::set('id', '3');

        // Parametre du nouveau groupe
        $id = Input::set('nom', 'aDmIn');
        $id = Input::set('groupeMail', 'groupeMailTOTO');
        $id = Input::set('groupeTelephone', 'groupeTelephoneTOTO');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());

        // On teste le retour de add
        $this->assertEquals(FALSE, $retour->success, "gestionGroupeModErreur() GET -> non succès");
        $this->assertEquals("500", $retour->code, "gestionGroupeModErreur() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeModErreur() GET -> payload");
        $this->assertEquals("Action impossible car le groupe aDmIn existe déjà (ADMIN).", $retour->payload, "gestionGroupeModErreur() Mauvaise chaine de payload");
    }

    // Ajout des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeDelErreur() {

        $oper = Input::set('oper', 'del');
        $id = Input::set('id', '999');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());
        
        // On teste le retour de add
        $this->assertEquals(FALSE, $retour->success, "gestionGroupeDelErreur() GET -> non succès");
        $this->assertEquals("500", $retour->code, "gestionGroupeDelErreur() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeDelErreur() GET -> payload");
        $this->assertEquals("Suppression impossible car le groupe n'existe pas.", $retour->payload, "gestionGroupeDelErreur() Mauvaise chaine de payload");
    }

    // Erreur des Groupes - Ne sera pas exécuté seul
    private function gestionGroupeErreur() {

        $this->gestionGroupeAddErreur();
        $this->gestionGroupeModErreur();
        $this->gestionGroupeDelErreur();

    }


    // Suppression d'un Groupes - Seul ce test sera exécuté car il fera tout d'un coup
    private function gestionGroupeDel() {

        $oper = Input::set('oper', 'del');
        $id = Input::set('id', '2');

        // chargement d'une liste de groupe
        $retour = json_decode($this->groupeController->gestionGroupe());
        // On teste le retour de add
        $this->assertEquals(TRUE, $retour->success, "gestionGroupeDel() GET -> non succès");
        $this->assertEquals("", $retour->code, "gestionGroupeDel() GET -> code retour");
        $this->assertObjectHasAttribute("payload", $retour, "gestionGroupeDel() GET -> payload");

        $liste = $this->getGroupesListe(TRUE);

        // test de cohérence
        $this->assertCount($liste->records, $liste->rows, "gestionGroupeDel() -> liste->records différent de liste->rows");
        $this->assertEquals($liste->records, 5, "gestionGroupeDel() -> liste->records différent de 5");

        // tests de l'objet retourné
        $this->assertObjectHasAttribute("records", $liste, "gestionGroupeDel() -> records absent");
        $this->assertObjectHasAttribute("page", $liste, "gestionGroupeDel() -> page absent");
        $this->assertObjectHasAttribute("total", $liste, "gestionGroupeDel() -> total absent");
        $this->assertObjectHasAttribute("rows", $liste, "gestionGroupeDel() -> rows absent");

        // tests de celui que l'on a ajouté
        $celuiQuiReste = $liste->rows[0];

        $this->assertObjectHasAttribute("id", $celuiQuiReste, "gestionGroupeDel() -> id absent");
        $this->assertObjectHasAttribute("cell", $celuiQuiReste, "gestionGroupeDel() -> cell absent");
        $this->assertEquals(10, count($celuiQuiReste->cell), "gestionGroupeDel() -> nombre de données différents de 10 pour celui qu'on a ajouté");

        // tests de non nullité de l'id et du nom du groupe testé
        $this->assertEquals(1, $celuiQuiReste->id, 'gestionGroupeDel() id != 1');
        $this->assertEquals(1, $celuiQuiReste->cell[0], 'gestionGroupeDel() cell[0] != 1');
    }

}
