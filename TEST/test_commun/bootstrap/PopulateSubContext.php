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
    private $moteur = FALSE;
    private $moteurModele = NULL;
    private $now = FALSE;

    // Pour les recherches dans les fichiers NAGIOS du collecteur
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
        Utils::initFakeServeur();
        Utils::truncateAll();
    }


    /* ********************************************************
     * GESTION DES UTILISATEURS - LOGIN
     ********************************************************** */

    /**
     * @Given /^je cré les utilisateurs:$/
     */
    public function jeCreLesUtilisateurs(TableNode $users) {

        // Création des utilisateurs
        foreach( $users->getHash() as $unUser) {
            $objUtilisateur = new Utilisateur();
            $objUtilisateur->nom = $unUser['nom'];
            $objUtilisateur->prenom = $unUser['nom'];
            $objUtilisateur->login = $unUser['nom'];
            $objUtilisateur->email = $unUser['nom']."@betfip.fr";
            $objUtilisateur->password =  $unUser['password'];
            $objUtilisateur->promotion = 0;
            $objUtilisateur->isactif = $unUser['isActif'];
            $objUtilisateur->isadmin = $unUser['isAdmin'];
            $objUtilisateur->save();
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
     * @Given /^un ind\.etat (\S+) sur (\S+) de formule d\'hypervision (.*)$/
     */
    public function unIndEtatHSurTestDeFormuleDHypervisionPluscritEqDcEqDcEqDc($nomIE, $nomEquipement, $formule) {

    $porteur = PorteurDIndicateur::where("nomComplet", 'like', "%$nomEquipement")->first();
        SinapsApp::$config["log.compilation.writers"] = 'MemoryLogWriter';
        SinapsApp::registerLogger("CompilationLogger", "compilation");


        $ie = new \models\configuration\IndicateurEtat();
        $ie->nom = $nomIE;
        $porteur->indicateursEtat()->save($ie);

    $hypervisionService = new HypervisionService();
        $hypervisionService->convertirFormule($formule, $ie, $porteur->application);
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

    /**
     * @Given /^un ind\.graph (\S+) sur (\S+) avec les séries:$/
     */
    public function unIndGraphSurDeFormule($indGraph, $equip, TableNode $series) {
        $porteur = PorteurDIndicateur::where("nomComplet", 'like', "%$equip")->first();

        $ig = new IndicateurGraphe();
        $ig->nom = $indGraph;
        $ig->libelle = "libelle d IG";
        // Ajout des NOT NULL
        $ig->title = "titre";
        $ig->stackSeries = "stackSeries";
        $ig->fillSeries = "fillSeries";
        $ig->optShowBars = 0;
        $ig->optShowPoints = 0;
        $ig->optShowLines = 1;
        $ig->bgImage = "bgImage";
        $ig->abscisse_libelle = "abscisse_libelle";
        $ig->abscisse_echelleDebut = "abscisse_echelleDebut";
        $ig->abscisse_echelleFin = "abscisse_echelleFin";
        $ig->ordonne_libelle = "ordonne_libelle";
        $ig->ordonne_echelleDebut = "ordonne_echelleDebut";
        $ig->ordonne_echelleFin = "ordonne_echelleFin";

        $ig->porteurDIndicateur()->associate($porteur);

        foreach( $series->getHash() as $uneSerie) {
            $serie = new Serie();
            $serie->sourceValeur = $uneSerie['Source'];
            $serie->libelle = $uneSerie['Libelle'];
            $ig->series()->save($serie);
        }
    }

    /**
     * @Given /^une donnée collectée (\S+) sur (\S+) de valeur (.*)$/
     */
    public function uneDonneeCollecteeSurDeValeur($dcName, $equip, $valeur) {
        $donnneCollectee = DonneeCollectee::where("nom", "%.$equip.$dcName")->first();

        if ($donnneCollectee === NULL) {
            $dc = new DonneeCollectee();
            $dc->nom = $equip . "." . $dcName;
            $dc->valeur = $valeur;
            $dc->date = App::make("TimeService")->now();

            $porteur->derniereDonnee()->save($dc);
        } else {
            $donnneCollectee->valeur = $valeur;
            $donnneCollectee->date = App::make("TimeService")->now();
            $donnneCollectee->save();
        }
    }

   /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) est à l\'état (\S+)$/
     */
    public function lIndEtatSurEstALEtat($ieName, $eqName, $etat) {
        $indicateur = IndicateurEtat::where("nomComplet", "like", "%.$ieName")->first();

        if($indicateur !== NULL ) {
            $dc = $indicateur->getDerniereDonnee();

            if ($dc === NULL) {
                $dc = new DonneeCollectee();
                $dc->nom = $indicateur->getNomComplet();
                $dc->valeur = Sinaps_Etats::asInt($etat);
                $dc->date = SinapsApp::make("TimeService")->now();

                $dc->save();
            } else {
                $dc->valeur = $etat;
                $dc->date = SinapsApp::make("TimeService")->now();
                $dc->save();
            }
        }
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) est à l\'état (\S+) dans le memcache$/
     */
    public function lIndEtatEstALEtatDansMemcache($ieName, $eqName, $etat) {

        $indicateur = IndicateurEtat::where("nomComplet", "like", "%.$ieName")->first();

        if ($indicateur->getDerniereDonnee() === NULL) {
            $dc = new DonneeCollectee();
            $dc->nom = $indicateur->getNomComplet() . "." . $ieName;
            $dc->valeur = Sinaps_Etats::asInt($etat);
            $dc->date = SinapsApp::make("TimeService")->now();

            $indicateur->derniereDonnee()->save($dc);
        } else {
            $indicateur->derniereDonnee->valeur = $etat;
            $indicateur->derniereDonnee->date = SinapsApp::make("TimeService")->now();
            $indicateur->derniereDonnee->save();
        }
    }

    /**
     * @Given /^l\'ind\.etat (\S+) doit être à l\'état (\S+) dans le memcache$/
     */
    public function lIndEtatDoitEtreALEtatDansMemcache($nomIE, $etatCible) {

        $data = SinapsMemcache::get($nomIE);

        assertNotEquals($data, FALSE, "L'indicateur $nomIE n'est pas présent dans le memcache");
        assertEquals(Sinaps_Etats::asInt($etatCible), $data->valeur, "$nomIE: valeur souhaitée $etatCible: valeur actuelle ". Sinaps_Etats::asText($etatCible));
    }

    /**
    * @Given /^je ne devrais pas avoir l\'indicateur (\S+) défini en base$/
    */
     public function bddJeNeDevraisPasAvoirDefini($nomIndicateur)
     {

         $obj = IndicateurEtat::where('nomComplet', $nomIndicateur)->get();
         assertEquals(0, count($obj));
         $obj = IndicateurCalcule::where('nomComplet', $nomIndicateur)->get();
         assertEquals(0, count($obj));
         $obj = IndicateurGraphe::where('nom', $nomIndicateur)->get();
         assertEquals(0, count($obj));
     }

    /**
    * @Given /^je devrais avoir l\'indicateur (\S+) défini en base$/
    */
     public function bddJeDevraisAvoirDefini($nomIndicateur)
     {
         $total = 0;
         $obj = IndicateurEtat::where('nomComplet', $nomIndicateur)->get();
         $total += count($obj);
         $obj = IndicateurCalcule::where('nomComplet', $nomIndicateur)->get();
         $total += count($obj);
         $obj = IndicateurGraphe::where('nom', $nomIndicateur)->get();
         $total += count($obj);
         assertEquals(1, $total);
     }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) a un délai de prise en compte de (\d+) mins$/
     */
    public function lIndEtatSurAUnDelaiDePriseEnCompteDeMins($ieName, $eqName, $mins) {
        $indicateur = IndicateurEtat::where("nom", $ieName)->first();

        $indicateur->maxWait = $mins;

        $indicateur->save();
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) requiert la creation d\'une fiche incident$/
     */
    public function lIndEtatRequiertFiche($ieName, $eqName) {
        $indicateur = IndicateurEtat::where("nom", $ieName)->first();

        $indicateur->creationFicheObligatoire = 1;

        $indicateur->save();
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) ne requiert pas la creation d\'une fiche incident$/
     */
    public function lIndEtatNeRequiertPasFiche($ieName, $eqName) {
        $indicateur = IndicateurEtat::where("nom", $ieName)->first();

        $indicateur->creationFicheObligatoire = 0;

        $indicateur->save();
    }

    /**
     * Le calcul de l'incubation ayant lieu de la compilation on doit la simuler de ce coté-ci
     *
     * @param IndicateurEtat $indicateur
     */

    public function simulerDeploiementPourCalculIncubation(IndicateurEtat $indicateur) {

        if($indicateur->maxWait === NULL) {
                $indicateur->maxWait = 0;
        }

        if($indicateur->creationFicheObligatoire == 0 && $indicateur->maxWait < 10 ) {
            $indicateur->maxWait = 10;
        }

        $indicateur->save();
    }

    /**
     * @Given /^exit$/
     */
    public function exitNow() {
        exit(1);
    }

    public function creationAnomalies($appli) {

        $ano = array(   'COLLECTES_ABSENTES' => $appli.'._DC_COLLECTES_ABSENTES',
                        'ERREUR_SYNTAXE_INDICATEUR' => $appli . '._DC_ERREUR_SYNTAXE_INDICATEUR',
                        'ALERTE_BAGOTAGE' => $appli . '._DC_ALERTE_BAGOTAGE'
                        );
        foreach( $ano as $nomIE => $nomDC ) {
            $eq = PorteurDIndicateur::where("nomComplet", "like", "%".$appli)->first();
            $ie = new IndicateurEtat();
            $ie->nom = $nomIE;
            $ie->genAlerte = 1;
            $ie->creationFicheObligatoire = 0;
            $ie->porteurDIndicateur()->associate($eq);
            $ie->destinataireAlerte = "EA";
            $ie->save();

            $indicateurs = array();
            $indicateurs[] = array(
                                    'Op' => 'ET',
                                    'Source' => $nomDC,
                                    'Unknown' => 'n= 3',
                                    'Critical' => 'n= 2',
                                    'Warning' => 'n= 1',
                                    'Ok' => 'n= 0',
                                    'Message' => ''
                                );
            $this->saveUneTableDeVerite($ie, TableDeVerite::ET, 0, $indicateurs, "", 0);
        }
    }

    /** ****************************************************************
     * INDICATEURS D ETAT
     **************************************************************** */
    /**
     * @Given /^un ind\.etat (\S+) sur (\S+) de formule:$/
     */
    public function unIndEtatSurDeFormule($nomIE, $nomEq, TableNode $tdv) {
        $eq = PorteurDIndicateur::where("nomComplet", "like", "%".$nomEq)->first();

        $ie = new IndicateurEtat();
        $ie->nom = $nomIE;
        $ie->creationFicheObligatoire = 0;

        $ie->porteurDIndicateur()->associate($eq);

        $formulePartielle = array();
        $typeDeTable = TableDeVerite::ET;
        $msg = NULL;
        $cardinalite = 0;
        $ordre = 0;

        foreach( $tdv->getHash() as $ligneDeTdv) {

            if ( $ligneDeTdv["Op"] == "ET") {
                $typeDeTable = TableDeVerite::ET;
            }
            if (preg_match("/(\d+)ParmiN/", $ligneDeTdv["Op"], $matches)) {
                $typeDeTable = TableDeVerite::X_PARMI_N;
                $cardinalite = $matches[1][0];
            }
            if( !empty($ligneDeTdv["Message"]))
                $msg = $ligneDeTdv["Message"];

            if( $ligneDeTdv["Op"] === "OU") {
                $this->saveUneTableDeVerite($ie, $typeDeTable, $cardinalite, $formulePartielle, $msg, $ordre);
                $ordre++;
                $formulePartielle = array();
                continue; // On n'ajoute pas cette ligne qui est vide
            }

            $formulePartielle[] = $ligneDeTdv;
        }
        if (count($formulePartielle) > 0 )
            $this->saveUneTableDeVerite($ie, $typeDeTable, $cardinalite, $formulePartielle, $msg, $ordre);

    }

     function saveUneTableDeVerite($indEtat, $type, $xParmiN, $indicateurs, $msg, $ordre) {
        $tdv = new TableDeVerite();
        $tdv->type = $type;
        $tdv->XParmiN = $xParmiN;
        $tdv->message = $msg;
        $tdv->ordre = $ordre;
        $prepareFormule = array();
        foreach( $indicateurs as $indicateur) {
            $critical = explode(" ", $indicateur["Critical"]);
            $warning = explode(" ", $indicateur["Warning"]);
            $ok = explode(" ", $indicateur["Ok"]);
            $unknown = explode(" ", $indicateur["Unknown"]);

            $prepareFormule[] = array(
                "name" => $indicateur["Source"],
                "critical_comp" => $critical[0],
                "critical_value" => ((count($critical) > 1) ? $critical[1] : ""),
                "warning_comp" => $warning[0],
                "warning_value" => ((count($warning) > 1) ? $warning[1] : ""),
                "ok_comp" => $ok[0],
                "ok_value" => ((count($ok) > 1) ? $ok[1] : ""),
                "unknown_comp" => $unknown[0],
                "unknown_value" => ((count($unknown) > 1) ? $unknown[1] : "")
            );
        }

        $tdv->formule = json_encode($prepareFormule);

        $indEtat->tablesDeVerite()->save($tdv);
    }

    /**
     * @Given /^l\'application (\S+) a (\d+) ind\.etat sur (\S+) déclenchant une alerte$/
     */
    public function lApplicationAXIndEtatDeclenchantUneAlerte($nomApp, $nombre, $nomEq) {
        // On crée un équipement
        $hierarchie = explode('.', $nomApp);

        $this->lEquipement($hierarchie[0], $hierarchie[1], $hierarchie[2], "groupeEquipement$nomEq", $nomEq);
        $eq = PorteurDIndicateur::where("nomComplet", "like", "%".$nomEq)->first();

        for($cpt=0;$cpt<$nombre;$cpt++) {
            $ie = new IndicateurEtat();
            $ie->genAlerte = 1;
            $ie->nom = "ETAT_BIDON_AVEC_ALERTE" . $cpt;
            $source = "METRIQUE.BIDON" . $cpt;
            $ie->porteurDIndicateur()->associate($eq);

            $formulePartielle = array(
                array(
                    "Op"=>"ET",
                    "Source"=> $source,
                    "Unknown"=>"t=",
                    "Critical"=>"n<= 10",
                    "Warning"=>"n<= 20",
                    "Ok"=>"n<= 100",
                    "Message"=>"Alerte de test"
                )
            );
            $typeDeTable = TableDeVerite::ET;
            $msg = "message pour $ie->nom";
            $cardinalite = 0;
            $ordre = 0;

            $this->saveUneTableDeVerite($ie, $typeDeTable, $cardinalite, $formulePartielle, $msg, $ordre);
        }
    }

    /**
     * @Given /^l\'application (\S+) a (\d+) ind\.etat sur (\S+) ne déclenchant pas d'alerte$/
     */
    public function lApplicationAXIndEtatNeDeclenchantPasUneAlerte($nomApp, $nombre, $nomEq) {
        // On cré un équipement
        $hierarchie = explode('.', $nomApp);

        $this->lEquipement($hierarchie[0], $hierarchie[1], $hierarchie[2], "groupeEquipement", $nomEq);
        $eq = PorteurDIndicateur::where("nomComplet", "like", "%".$nomEq)->first();

        for($cpt=0;$cpt<$nombre;$cpt++) {
            $ie = new IndicateurEtat();
            $ie->nom = "ETAT_BIDON_SANS_ALERTE" . $cpt;
            $source = "METRIQUE.BIDON" . $cpt;
            $ie->porteurDIndicateur()->associate($eq);

            $formulePartielle = array(
                array(
                    "Op"=>"ET",
                    "Source"=> $source,
                    "Unknown"=>"t=",
                    "Critical"=>"n<= 10",
                    "Warning"=>"n<= 20",
                    "Ok"=>"n<= 100",
                    "Message"=>"Alerte de test"
                )
            );
            $typeDeTable = TableDeVerite::ET;
            $msg = NULL;
            $cardinalite = 0;
            $ordre = 0;

            $this->saveUneTableDeVerite($ie, $typeDeTable, $cardinalite, $formulePartielle, $msg, $ordre);
        }
    }

    /**
     * @Given /^une donnée collectée memcache (\S+) de valeur (.*)$/
     */
    public function uneDonneeCollecteeMemcacheSurDeValeur($dcFullName, $valeur) {
        SinapsMemcache::set($dcFullName, $valeur, 300, App::make("TimeService")->now(), 1);
    }

     /**
     * @Given /^une donnée collectée memcache (\S+) absente$/
     */
    public function uneDonneeCollecteeMemcacheAbsente($dcFullName) {
        SinapsMemcache::delete($dcFullName);
    }

    /**
     * @Given /^la donnée collectée (\S+) sur (\S+) passe à (\d+) après (\d+) mins$/
     */
    public function laDonneeCollecteeSurEqPasseAApresMins($nomDC, $nomEq, $valeur, $delai) {
        $this->mainContext->moveTime($delai);

        $this->uneDonneeCollectee_Sur_DeValeur($nomDC, $nomEq, $valeur);
    }

    /**
     * @Given /^la donnée collectée memcache (\S+) passe à (\S+) après (\d+) mins$/
     */
    public function laDonneeCollecteeMemcachePasseAApresMins($nomDC, $valeur, $delai) {
        $this->mainContext->moveTime($delai);

        SinapsMemcache::set($nomDC, $valeur, 300, App::make("TimeService")->now(), 1);
    }

    /** *******************************************
     * DEROGATION
     ******************************************** */
    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) ne déclenche pas une alerte$/
     */
    public function derogLIndEtatSurDeclencheUneAlerte($nomIE, $nomEq) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }
        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now(), 0);

        $derogation->genAlerte = 0;
        $derogation->save();
    }

    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) a une durée de confirmation de (\d+) minutes$/
     */
    public function derogLIndEtatSurAUneDureeDeConf($nomIE, $nomEq, $minutes) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }
        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now(), 0);
        $derogation->maxWait = $minutes;
        $derogation->save();
    }

    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) n\'a plus de pour plage de non déclenchement$/
     */
    public function derogLIndEtatSurNAPasUnePlageNonDeclenchement($nomIE, $nomEq) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now(), 0);

        $derogation->genPlage = 1;
        $derogation->plagesNonDeclenchement = null;
        $derogation->save();
    }

    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) a pour plage de non déclenchement:$/
     */
    public function derogLIndEtatSurAUnePlageNonDeclenchement($nomIE, $nomEq, TableNode $plages) {

        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now()+1, 0);

        $derogation->genPlage = 1;

        $tab = array();
        foreach( $plages->getHash() as $plage) {
            $tab[] = $plage['plage'];
        }

        $derogation->plagesNonDeclenchement = join("\n", $tab);

        $derogation->save();

    }

    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) a pour consigne "([^"]*)"$/
     */
    public function derogLIndEtatSurAUneConsigne($nomIE, $nomEq, $consigne) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now(), 0);

        $derogation->genConsigne = 1;
        $derogation->consigne = $consigne;
        $derogation->save();
    }

    /**
     * @Given /^par dérogation, l\'ind\.etat (\S+) sur (\S+) a pour destinataire "(.*)"$/
     */
    public function derogLIndEtatSurAUnDestinataire($nomIE, $nomEq, $dest) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        // Ajout d'un profil pour l'utilisateur
        // Recherche de
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);
        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat',$indicateur->nomComplet)->first();

        if( $derogation === null ) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $appName = explode('.', $indicateur->nomComplet);
        $appName = $appName[0];
        SinapsMemcache::set( $appName.".aBesoinDetreReconstruit", App::make("TimeService")->now(), 0);

        $groupe = Groupe::where('nom', $dest)->first();
        if(!$groupe) {
            $groupe = new Groupe();
            $groupe->nom = $dest;
            $groupe->save();
        }
        $groupeId = $groupe->id;

        $derogation->destinataireAlerte = $groupeId;
        $derogation->save();
    }

    /** *******************************************
     * ALERTES
     ******************************************** */
    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) déclenche une alerte$/
     */
    public function lIndEtatSurDeclencheUneAlerte($nomIE, $nomEq) {
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $indicateur->genAlerte = 1;

        $indicateur->save();
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) a pour destinataire "(\S+)"$/
     */
    public function lIndEtatSurAPourDestinataire($nomIE, $nomEq, $destinataire) {
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $indicateur->destinataireAlerte = $destinataire;

        $indicateur->save();
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) est en maintenance$/
     */
    public function lIndEtatSurMaintenance($nomIE, $nomEq) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);

        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat', $indicateur->nomComplet)->first();

        if($derogation === NULL) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $derogation->maintenance = 1;

        $derogation->save();
    }

    /**
     * @Given /^l\'ind\.etat (\S+) sur (\S+) n\'est plus en maintenance$/
     */
    public function lIndEtatSurPasMaintenance($nomIE, $nomEq) {
        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);

        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurEtat::where("nom", $nomIE)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat', $indicateur->nomComplet)->first();

        if($derogation === NULL) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $derogation->maintenance = 0;

        $derogation->save();
    }

    /**
     * @Given /^l\'ind\.calc (\S+) sur (\S+) est en maintenance$/
     */
    public function lIndCalcSurMaintenance($nomIC, $nomEq) {

        $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
        $objEq = Equipement::where('fqdn', $nomEq)
                                        ->first();

        $nomLong = explode('.', $objEq->getNomLong());
        $nomApplication = array_shift($nomLong);

        $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApplication);

        $this->jeMeConnecteAvec('bob', 'bobo1');
        $indicateur = IndicateurCalcule::where("nom", $nomIC)->first();

        $derogation = Derogation::where('nomCompletIndicateurEtat', $indicateur->nomComplet)->first();

        if($derogation === NULL) {

            $derogation = new Derogation();
            $derogation->nomCompletIndicateurEtat = $indicateur->nomComplet;
        }

        $derogation->maintenance = 1;

        $derogation->save();
    }

    /**
     * @Given /^je vais modifier le statut nouvelle à suspendue pour l'alerte de type (\S+) pour l\'ind\.etat (\S+) de (\S+) jusqu\'à dans (\d+) minutes$/
     */
    public function jeVaisModifierLeStatutNouvelleASuspendue($typeAlerte, $nomIE, $nomEq, $delai) {

        $this->mainContext->setUtilisateurCourant(Utilisateur::find(1));

        $ie = IndicateurEtat::where("nom", $nomIE)->first();

        $uniqueAlerte = Alerte::where('nomCompletIndicateurEtatDeclencheur', $ie->getNomComplet())
                              ->where('etat', Sinaps_Etats::asInt($typeAlerte))
                              ->where('statutAlerte', Alerte::NOUVELLE)
                              ->first();

        $controller = new AlerteController();
        Input::set("ids", array($uniqueAlerte->id));
        $dateFinSuspension = App::make("TimeService")->now()+$delai*60;
        Input::set("dateFinSuspension", date("d/m H:i:s", $dateFinSuspension));
        $controller->postSuspendre();
        $this->mainContext->jeDevraisAvoirUneAlerteSuspenduePourLIndEtatDe($typeAlerte, $nomIE, $nomEq);
    }

    /**
     * @Given /^je vais modifier le statut nouvelle à ignorée pour l'alerte de type (\S+) pour l\'ind\.etat (\S+) de (\S+)$/
     */
    public function jeVaisModifierLeStatutNouvelleAIgnoree($typeAlerte, $nomIE, $nomEq) {

        $this->mainContext->setUtilisateurCourant(Utilisateur::find(1));

        $ie = IndicateurEtat::where("nom", $nomIE)->first();

        $uniqueAlerte = Alerte::where('nomCompletIndicateurEtatDeclencheur', $ie->getNomComplet())
                              ->where('etat', Sinaps_Etats::asInt($typeAlerte))
                              ->where('statutAlerte', Alerte::NOUVELLE)
                              ->first();

        $controller = new AlerteController();
        Input::set("ids", array($uniqueAlerte->id));
        $controller->postIgnorer();
        $this->mainContext->jeDevraisAvoirUneAlerteIgnoreePourLIndEtatDe($typeAlerte, $nomIE, $nomEq);
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
     *
     * @Given /^les habilitations pour tous les projets sont les suivantes:$/
     * @param \Behat\Gherkin\Node\TableNode $listeHabilitations :
     * | login | groupe | application | profil |
     */
    public function habilitationsSurLesApplications(TableNode $listeHabilitations) {

        foreach( $listeHabilitations->getHash() as $ligneDHabilitations) {
            // Récupération des éléments du tableau :
            $login = $ligneDHabilitations['login'];
            $nomGroupe = $ligneDHabilitations['groupe'];
            $nomApplication = $ligneDHabilitations['application'];
            $nomProfil = $ligneDHabilitations['profil'];

            $user = Utilisateur::where("login", $login)->first();
            assertNotEquals(NULL, $user );

            $application = Application::where("nom", $nomApplication)->first();
            assertNotEquals(NULL, $application );

            $profil = Profil::where("nom", $nomProfil)->first();
            assertNotEquals(NULL, $profil );

            $groupe = Groupe::where("nom", $nomGroupe)->first();
            if (!$groupe) {
                $groupe = new Groupe();
                $groupe->nom = $nomGroupe;
                $groupe->save();
            }
            // Insertion du lien Utilisateur -> Groupe
            $utilisateurDuGroupe = new UtilisateurDuGroupe();
            $utilisateurDuGroupe->Utilisateur_id = $user->id;
            $utilisateurDuGroupe->Groupe_id = $groupe->id;
            $utilisateurDuGroupe->save();

            // Insertion du lien Utilisateur -> profil
            $profilDeLUtilisateur = new ProfilDeLUtilisateur();
            $profilDeLUtilisateur->Application_id = $application->id;
            $profilDeLUtilisateur->Utilisateur_id = $user->id;
            $profilDeLUtilisateur->Profil_id = $profil->id;
            $profilDeLUtilisateur->save();

            // Insertion du lien Application -> Groupe
            $applicationDuGroupe = new ApplicationDuGroupe();
            $applicationDuGroupe->Application_id = $application->id;
            $applicationDuGroupe->nomGroupe = $groupe->nom;
            $applicationDuGroupe->Groupe_id = $groupe->id;
            $applicationDuGroupe->Profil_id = $profil->id;
            $applicationDuGroupe->save();
        }
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

    /** ***************************************************************************
     *
     * MIS EN ATTENTE
     *
     *
     **************************************************************************** */

    /**
     * exemple de chaîne attendu
     *
     * @Given /^j\'ajoute l\'ind\.etat (\S+) à la liste des indicateurs en attente$/
     */
    public function jAjouteALaListeAttenteLIndPourLAppli($ind) {
        $explose = explode('.', $ind);
        $nomApp = array_shift($explose);

        // On récupère la liste et on la met à jour
        $listeEnAttente = SinapsMemcache::get($nomApp. ".IEEnAttente");
        if( $listeEnAttente === FALSE ) {
            $listeEnAttente = array($ind);
        } else {
            $listeEnAttente = $listeEnAttente->valeur;
            $listeEnAttente[] = $ind;
        }
        SinapsMemcache::set($nomApp. ".IEEnAttente", $listeEnAttente, 0, App::make("TimeService")->now());
    }

    /**
     * exemple de chaîne attendu
     *
     * @Given /^je prends en compte l\'alerte (\S+) de l\'ind\.etat (\S+)$/
     */
    public function jePrendsEnCompteLAlerteDeLIndEtat($statutAlerte, $ind ) {
        $explose = explode('.', $ind);
        $nomApp = array_shift($explose);

        // On récupère l'id de l'alerte montée
        $alerte = Alerte::where('nomCompletIndicateurEtatDeclencheur', $ind)
                        ->where('statutAlerte', constant("Alerte::$statutAlerte"))->first();

        if(Utilisateur::where('login','bob')->first() === NULL ) {
            $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
            $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApp);
        }
        $this->jeMeConnecteAvec('bob', 'bobo1');

        $controller = new AlerteController();
        Input::set("ids", $alerte->id);
        Input::set("niveau", "N0");
        $controller->postAcquitter();

        $alerte = Alerte::find($alerte->id);
        assertEquals($alerte->statutAlerte, Alerte::PRISE_EN_COMPTE);
    }

    /**
     * exemple de chaîne attendu
     *
     * @Given /^je saisie le numéro d\'incident (\S+) pour l\'alerte de l\'ind\.etat (\S+)$/
     */
    public function jeSaisieLeNumeroDIncidentPourLAlerteDeLIndEtat($numIncident, $ind) {
        $explose = explode('.', $ind);
        $nomApp = array_shift($explose);

        // On récupère l'id de l'alerte montée
        $alerte = Alerte::where('nomCompletIndicateurEtatDeclencheur', $ind)
                        ->where('statutAlerte', Alerte::PRISE_EN_COMPTE)->first();

        if(Utilisateur::where('login','bob')->first() === NULL ) {
            $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
            $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApp);
        }
        $this->jeMeConnecteAvec('bob', 'bobo1');

        $controller = new AlerteController();
        Input::set("id", $alerte->id);
        Input::set("numero_incident", $numIncident);
        $controller->postNumeroIncident();

        $alerte = Alerte::find($alerte->id);
        assertEquals($alerte->statutAlerte, Alerte::SIGNALEE);
    }
    /**
     * exemple de chaîne attendu
     *
     * @Given /^je résoud l\'alerte de l\'ind\.etat (\S+)$/
     */
    public function jeResoudLAlerteDeLIndEtat($ind) {
        $explose = explode('.', $ind);
        $nomApp = array_shift($explose);

        // On récupère l'id de l'alerte montée
        $alerte = Alerte::where('nomCompletIndicateurEtatDeclencheur', $ind)
                        ->where('statutAlerte', Alerte::SIGNALEE)->first();

        if(Utilisateur::where('login','bob')->first() === NULL ) {
            $this->lUtilisateurAyantLeMotDePasse('bob', 'bobo1');
            $this->utilisateurALeProfilSur('bob', 'PROFILQUELCONQUE', $nomApp);
        }
        $this->jeMeConnecteAvec('bob', 'bobo1');

        $controller = new AlerteController();
        Input::set("ids", array($alerte->id));
        $controller->postResoudre();

        $alerte = Alerte::find($alerte->id);
        assertEquals($alerte->statutAlerte, Alerte::RESOLUE);

        // On récupère la liste et on la met à jour
        /*$listeEnAttente = SinapsMemcache::get($nomApp. ".IEEnAttente");
        if( $listeEnAttente === FALSE ) {
            $listeEnAttente = array($ind);
        } else {
            $listeEnAttente = $listeEnAttente->valeur;
            $listeEnAttente[] = $ind;
        }
        SinapsMemcache::set($nomApp. ".IEEnAttente", $listeEnAttente, 0, App::make("TimeService")->now());*/
        $dataMemcache = SinapsMemcache::get($nomApp. ".collectesAForcerSuiteResolu");
        assertNotEquals($dataMemcache, FALSE);
        $dataMemcache = array_pop($dataMemcache->valeur);

        // Tests sur les clefs
        assertContains( 'equipement',join(';',array_keys($dataMemcache)));
        assertContains('nomComplet', join(';',array_keys($dataMemcache)));
        assertContains('etiquette', join(';',array_keys($dataMemcache)));

        // Tests sur les valeurs
        assertContains(IndicateurEtat::where('nomComplet', $ind)->first()
                                     ->porteurDIndicateur
                                     ->equipement
                                     ->fqdn,
                       join(';',array_values($dataMemcache)));
        assertContains($ind, join(';',array_values($dataMemcache)));
        assertContains(';;', join(';',array_values($dataMemcache)));
    }

    /**
     * @Given /^le collecteur a pour l'application (\S+) les commandes nagios suivantes:$/
     */
    public function leCollecteurAPourLAppliLesCommandes($nomAppli, PyStringNode $string) {
        static::$fileBuffer["/collecteur/${nomAppli}_commands.cfg"] = $string->getRaw();
    }

    /**
     * exemple de chaîne attendu
     *
     * @Given /^le collecteur demande sa liste de collecte à forcer$/
     */
    public function leCollecteurDemandeSaListeDeCollectesAForcer() {

        $mockedGestionForceCollecteService = m::mock("GestionForceCollecteService");

        $check = function () {
           /**  - récupération de toutes les applications du collecteur
            *  - demande de collecte à forcer par application via curl (send)
            *  - récupération des collectes à forcer
            *  - forçage des collectes
            */
            $allApplicationsName = Application::all();
            foreach($allApplicationsName as $app) {
                $allApplications[] = $app->nom;
            }

            // Demande aux serveurs applicatifs
            $controller = new CollecteurController();
            Input::set('collecteur', 'collecteur');
            Input::set('data', serialize($allApplications));

            $retour = $controller->getCollecteAForcer();
            $retour = JsonService::parseResponse($retour);

            // récupération des collectes à forcer
            $cmdNagios = array();

            foreach($retour["payload"] as $application => $elements) {

                foreach($elements as $element) {

                    $str = "";
                    if( isset($element['equipement']) ) {
                        $str .= $element['equipement'] . ".";
                    }

                    $str .= $application. ".*";

                    if( !empty($element['etiquette']) ) {
                        $str .= $element['etiquette'] ."-.*";
                    }

                    $arrRes = array();
                    foreach( PopulateSubContext::$fileBuffer as $contenu) {
                        foreach(explode('define command {', $contenu) as $ligne) {

                            if(preg_match("/command_name.*" . $str . "/", $ligne)) {
                                $ligne = trim(preg_replace("/.*command_name.*(" . $str . ")\s+command_line.*/",
                                                           '$1',
                                                           str_replace("}","",$ligne)));
                                $arrRes[] = $ligne;
                            }
                        }

                    }
                    foreach($arrRes as $service) {
                        $aAjouter = array('service' => $service, 'host' => $element['equipement']);
                        $cmdNagios[] = $aAjouter;
                    }
                }
            }
            return $cmdNagios;
        };

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
        $cmdNagios = $service->check();

        assertEquals(count($cmdNagios), 3);
    }

    /** ***************************************************************************
     *
     * ISAC
     *
     *
     **************************************************************************** */


    /**
     * exemple de chaîne attendu
     *
     * @Given /^l\'ind.etat (\S+) sur (\S+) a le tag (\S+)$/
     */
    public function aLeTag($indEtat, $eq, $tag) {
        $IEBdd = IndicateurEtat::where('nom', $indEtat)->first();
        assertNotEquals($IEBdd, NULL);

        $tagBdd = Tag::where('nom', $tag)->first();
        if($tagBdd === NULL) {
            $tagBdd = new Tag();
            $tagBdd->nom = $tag;
            $tagBdd->save();
        }

        $IEduTagBdd = IndicateursDuTag::where("IndicateurEtat_id", $IEBdd->id)
                                      ->where("Tag_id", $tagBdd->id)->first();
        if($IEduTagBdd === NULL) {
            $IEduTagBdd = new IndicateursDuTag();
            $IEduTagBdd->Tag_id = $tagBdd->id;
            $IEduTagBdd->IndicateurEtat_id = $IEBdd->id;
            $IEduTagBdd->save();
        }
    }

  /**
     * exemple de chaîne attendu
     *
     * @Given /^l\'ind.etat (\S+) sur (\S+) a pour libelle (\S+)$/
     */
    public function aPourLibelle($indEtat, $eq, $libelle) {
        $IEBdd = IndicateurEtat::where('nom', $indEtat)->first();
        assertNotEquals($IEBdd, NULL);
        $IEBdd->libelle = $libelle;
        $IEBdd->save();
    }

    /**
     * exemple de chaîne attendu
     *
     * @Given /^un retour de scénarii de type:$/
     */
    public function unRetourDeScenariiDeType( TableNode $isac) {

        // Construction de la base de l'objet
        $data = new stdClass();
        $data->typeImport = "ISAC";
        $data->dateImport = date("Y-d-m H:i:s", App::make("TimeService")->now());
        $data->sourceImport = "http://isac.appli.dgfip/information/db_to_json.php";
        $data->description = "Etat scénarios ISAC";
        $data->liste = array();

        $sourceEvenement = "ISAC";
        $serveurSource = "localhost";

       /* Modèle pour ISAC
            array (
                'application'   => 'RSP',
                'nom'           => rsp_scenario_toto,
                'elements'      => array(
                                            array("RSP._DC_rsp_scenario_toto.MESSAGE"),
                                            array("RSP._DC_rsp_scenario_toto"),
                                            array(
                                                "RSP._DC_rsp_scenario_toto.NB_SCENE_NOK",
                                                "RSP._DC_rsp_scenario_toto.DUREE",
                                                120,
                                                30
                                            )
                                    ),
                'frequence'     => 600,
                'message'       => array("","OK - pas de problème",""),
                'date'          => "2014-04-07 14:01:22",
                'clefsMemcache' => array(
                                        "RSP._DC_rsp_scenario_toto" => "OK",
                                        "RSP._DC_rsp_scenario_toto.NB_SCENE_NOK"   => 0,
                                        "RSP._DC_rsp_scenario_toto.DUREE"          => 6.235
                                        "RSP._DC_rsp_scenario_toto.MESSAGE" => "OK - pas de problème"
                                    ),
                'type'          => 'isac')
        */

        foreach( $isac->getHash() as $ligne) {
            $tab = array();
            $tab['application'] = $ligne['application'];
            $tab['nom'] = $ligne['nom'];
            // Création de l'entrée elements
            $dc = $tab['application'] . "._DC_" . $tab['nom'];
            $tab['elements'][] = array("$dc.MESSAGE");
            $tab['elements'][] = array($dc);
            $tab['elements'][] = array( "$dc.NB_SCENE_NOK",
                                        "$dc.DUREE",
                                        $ligne['SC'],
                                        $ligne['SW']
                                       );
            $tab['frequence'] = $ligne['freq'];
            $tab['message'] = array("", $ligne['message'], "");
            $tab['date'] = $ligne['date'];
            // Création de l'entrée clefsMemcache
            $tab['clefsMemcache'] = array(
                                            $dc => $ligne['etat'],
                                            "$dc.NB_SCENE_NOK"  => $ligne['SNOK'],
                                            "$dc.DUREE"     => $ligne['duree'],
                                            "$dc.MESSAGE"   => $ligne['message'],
                                           );
            $tab['type'] = $ligne['type'];

            $data->liste[] = $tab;
        }

        // Intégration des données dans le service de traitement des scénarii
        SinapsApp::$config["log.import_evenement_exterieur.writers"] = 'MemoryLogWriter';
        SinapsApp::$config["log.import_evenement_exterieur.niveau"] = '0';

        SinapsApp::registerLogger("ImportEvenementExterieurLogger", "import_evenement_exterieur");

        $objImport = new importEvenementExterieurService($sourceEvenement, $data, $serveurSource);
        $objImport->prepareCreationIEDansMoteur();

    }


}
