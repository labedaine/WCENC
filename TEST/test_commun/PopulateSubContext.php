<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\Context,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\FeatureEvent;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use \Mockery as m;

$rootDir = __DIR__."/../../apps";
require_once $rootDir."/commun/php/Autoload.php";
require_once $rootDir."/../tests/test_commun/Utils.php";
require_once $rootDir."/restitution/services/Autoload.php";

require_once $rootDir."/commun/php/services/TimeService.php";

$heureCourante = 0;

/**
 * Features context.
 */

class PopulateSubContext implements Context
{
    private $now = FALSE;
    private static $sqlDialect = "POSTGRESQLœ";

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

    public function insertInitialData() {
        // Creating Dummy Objects
        $this->dummyCollecteur = new Collecteur();
        $this->dummyCollecteur->hostname = "dummy";
        $this->dummyCollecteur->ipv4 = "0.0.0.0";
        $this->dummyCollecteur->save();

        // Ajout des profils
        $listeProfils = array('N0', 'N1', 'N2', 'N3', 'administrateur');
        $idx=1; // Pour le niveau : = $idx -1
        foreach ($listeProfils as $nomProfil) {
            $objProfil = new Profil();
            $objProfil->nom = $nomProfil;
            $objProfil->niveau = ($idx - 1);
            $objProfil->save();
            $idx++;
        }

        // Création de l'utilisateur sinaps par défaut
        $usrSinaps = new Utilisateur();
        $usrSinaps->nom = 'sinaps';
        $usrSinaps->login = 'sinaps';
        $usrSinaps->email = 'sinaps';
        $usrSinaps->password = 'inactif';
        $usrSinaps->isActif = FALSE;
        $usrSinaps->save();

        // Création d'un Moteur
        $this->moteurModele = new Moteur();
        $this->moteurModele->informations = "test";
        $this->moteurModele->save();

        // Variables
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

    /**
     * @Given /^des requetes complexes$/
     */
    public function desRequetesComplexes() {


        if (App::$sqlDialect !== getenv("SINAPS_DEFAUT_DB")) {
            App::$sqlDialect = getenv("SINAPS_DEFAUT_DB");
            Utils::initFakeServeur(getenv("SINAPS_DEFAUT_DB"));
            Utils::truncateAll();
            $this->insertInitialData();
        }
    }


    /** *******************************************************
     * INDICATEURS CALCULES @TODO: faire un sous contexte
     ********************************************************* */
    /**
     * @Given /^l\'application ([^\.]+)\.([^\.]+)\.([^\.]+)$/
     */
    public function lApplication($nomMacroD, $nomDomaine, $nomAppli) {

        // Creating Dummy Objects
        $this->dummyCollecteur = new Collecteur();
        $this->dummyCollecteur->hostname = "dummy";
        $this->dummyCollecteur->ipv4 = "0.0.0.0";
        $this->dummyCollecteur->save();

        $macroD = MacroDomaine::where("nom", $nomMacroD)->first();
        if ($macroD === NULL) {
            $macroD = new MacroDomaine();
            $macroD->nom = $nomMacroD;
            $macroD->save();
        }

        $domaine = Domaine::where("nom", $nomDomaine)->first();
        if ($domaine === NULL) {
            $domaine = new Domaine();
            $domaine->nom = $nomDomaine;
            $macroD->domaines()->save($domaine);
        }

        $application = Application::where("nom", $nomAppli)->first();
        if ($application === NULL) {
            $application = new Application();
            $application->nom = $nomAppli;
            $application->HNO = 1;
            
            // On recherche le moteur si il est pas présent on l'ajoute
            $monMoteur = Moteur::find($this->moteurModele);
            
            if(!$monMoteur) {
				$monMoteur = new Moteur();
				$monMoteur->id = $this->moteurModele;
				$monMoteur->save();
			}
			$allMoteur = Moteur::all();
			$this->moteurModele = $allMoteur[0]->id;
            $application->Moteur_id = $this->moteurModele;
            $domaine->applications()->save($application);
            $this->lApplicationAPourExploitantSysOuApp($nomAppli, "exploitant_sys", "ExploitantSysteme BEHAT");
            $this->lApplicationAPourExploitantSysOuApp($nomAppli, "exploitant_app", "ExploitantApplicatif BEHAT");
        }

        return $application;
    }

    /**
     * @Given /^l\'application (\S+) a pour (\S+) "(.*)"$/
     */
    public function lApplicationAPourExploitantSysOuApp( $nomAppli, $equipe, $destinataire) {

        $application = Application::where("nom", $nomAppli)->first();
        $groupe=Groupe::where('nom', $destinataire)->first();
        if(!$groupe) {
            $groupe=new Groupe();
            $groupe->nom = $destinataire;
            $groupe->save();
        }
        $idGroupe = $groupe->id;
        $application->$equipe = $groupe->id;
        $application->save();
    }


    /**
     * @Given /^l\'équipement ([^\.]+)\.([^\.]+)\.([^\.]+)\.([^\.]+)\.([^\.]+)$/
     */
    public function lEquipement($nomMacroD, $nomDomaine, $nomAppli, $nomGroupe, $nomEquip) {
        $application = $this->lApplication($nomMacroD, $nomDomaine, $nomAppli);

        $groupeEquip = GroupeDEquipement::where("nom", $nomGroupe)->first();
        if ($groupeEquip === NULL) {
            $groupeEquip = new GroupeDEquipement();
            $groupeEquip->nom = $nomGroupe;
            $application->groupesDEquipement()->save($groupeEquip);
        }

        $equipement = Equipement::where("fqdn", $nomEquip)->first();
        if ($equipement === NULL) {
            $equipement = new Equipement();
            $equipement->fqdn = $nomEquip;
            $equipement->ipv4 = "1.1.1.$this->ipCount";
            $this->ipCount++;
            $equipement->Collecteur_id = $this->dummyCollecteur->id;
            $groupeEquip->equipements()->save($equipement);
        }
    }

    /**
     * @Given /^un ind\.calc (\S+) sur (\S+) de formule (.+)$/
     */
    public function unIndCalcSurDeFormule($indCalc, $equip, $formule) {
        $porteur = PorteurDIndicateur::where("nomComplet", 'like', "%$equip")->first();

        $ic = new IndicateurCalcule();
        $ic->nom = $indCalc;
        $ic->libelle = "libelle d IC";
        $ic->formule = $formule;
        $ic->porteurDIndicateur()->associate($porteur);
    }

    //$dc->date = SinapsApp::make("TimeService")->now();


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
     * @Given /^l\'utilisateur (\S+) ayant le mot de passe (\S+)$/
     */
    public function lUtilisateurAyantLeMotDePasse($login, $passwd) {
        $user = Utilisateur::where('login',$login)
                          ->where('password',$passwd)
                          ->first();

        if($user === NULL) {
            $user = new Utilisateur();
        }

        $user->nom = $login;
        $user->prenom = $login;
        $user->login = $login;
        $user->email = "$login@nowhere.dgfip.fr";
        $user->password = $passwd;
        $user->save();
        
        // Ajout des profils
        $listeProfils = array('N0', 'N1', 'N2', 'N3', 'administrateur');
        $idx=1; // Pour le niveau : = $idx -1
        foreach ($listeProfils as $nomProfil) {
			$objProfil = Profil::where('nom', $nomProfil)->first();
			if(!$objProfil) {
				$objProfil = new Profil();
				$objProfil->nom = $nomProfil;
				$objProfil->niveau = ($idx - 1);
				$objProfil->save();
			}
            $idx++;
        }
    }


    /**
     * @Given /^je me connecte avec (\S+) \/ (\S+)$/
     */
    public function jeMeConnecteAvec($login, $passwd) {
        $mock = MockedRestClient::getInstance();
        $mock->callRepartitionService();

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
        $this->token = $this->mainContext->getFromCookie(SinapsApp::$config['ou.suis.je'] . "_token");
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
    public function jeMeDeonnecte() {
		$this->mainContext->setInCookie(SinapsApp::$config['ou.suis.je'] . "_token", $this->token);
        $controller = new LoginController();
        $controller->deleteAuth();
    }
}
