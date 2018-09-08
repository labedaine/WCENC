#!/usr/bin/php
<?php
/**
 * Met des données dans la base de données
 *
 * PHP version 5
 *
 * @author Stéphane Gatto <stephane.gatto@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../Autoload.php";
require_once __DIR__."/../ressource/php/Autoload.php";

class PopulateScript extends SinapsScript {

    private $etatsMatch = array(1 => "SCHEDULED",
                                2 => "TIMED",
                                3 => "POSTPONED",
                                4 => "CANCELED",
                                5 => "IN_PLAY",
                                6 => "FINISHED");

    private $phasesMatch = array(1 => "Phase de groupes (1ière journée)",
                                 2 => "Phase de groupes (2ième journée)",
                                 3 => "Phase de groupes (3ième journée)",
                                 4 => "Huitièmes de finale",
                                 5 => "Quarts de finale",
                                 6 => "Demi-finales",
                                 7 => "Match pour la troisième place",
                                 8 => "Finale");

    public function __construct() {
        parent::__construct(__DIR__."/../config","PopulateLogger","populate");
        $this->logger = SinapsApp::make("PopulateLogger");


        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");
        $this->restClientService  = SinapsApp::make("RestClientService");
        $this->systemService  = SinapsApp::make("SystemService");

        $this->api = new ApiFootballDataService();

        $this->init();
    }

    public function configure() {
        $conf = $this->setName(__FILE__)
                     ->setDescription("(re)construit la base et la remplie avec les données initiales")

                     ->addOption(
                         "recreate",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "Recré la base de données"
                     )
                     ->addOption(
                        "test",
                        SinapsScript::FACULTATIF | SinapsScript::VALUE_NONE,
                        "charge les données dans la base de test"
                     );
    }

    public function performRun() {
        try {

            // On reconstruit la base si demandé
            if(isset($this->options->recreate)) {
                $this->recreateDb();

                // Et on la rempli
                $this->populate();
            }

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }

    private function init() {

        $this->srv = SinapsApp::getConfigValue("api.url");
        $this->timeoutCurl = SinapsApp::getConfigValue("api.timeout");
        $this->apiKey = SinapsApp::getConfigValue("api.key");
    }

    private function recreateDb() {

        // Récupération des variables
        $db_host    = SinapsApp::getConfigValue("db_host");
        $db_port    = SinapsApp::getConfigValue("db_port");
        $db_name    = SinapsApp::getConfigValue("db_name");
        $db_user    = SinapsApp::getConfigValue("db_user");
        $db_pass    = SinapsApp::getConfigValue("db_pass");

        // Cas de la base de test
        if(isset($this->options->test)) {
            $db_name = "test";
            $db_user = "test";
            $db_pass = "test";

            SinapsApp::$config['db_name'] = $db_name;
            SinapsApp::$config['db_user'] = $db_user;
            SinapsApp::$config['db_pass'] = $db_pass;

            SinapsApp::initDb();
            $dbh = SinapsApp::make("dbConnection");
            $dbh->beginTransaction();
        }

        // Recréation de la base
        $baseSql    = __DIR__ . "/../../BDD/base.sql";
        $cmd        = "psql -U $db_user -h $db_host -p $db_port $db_name < $baseSql";

        $this->logger->debuterEtape(
            "resetBdd",
            "(re)Construction de la base $db_name"
        );

        $this->logger->addInfo("Exécution du script $baseSql");
        $this->logger->addInfo($cmd);
        $this->systemService->shellExecute("psql -U $db_user -h $db_host -p $db_port $db_name < $baseSql");

        $this->logger->finirEtape(
            "(re)Construction terminée",
            "resetBdd"
        );
    }

    private function populate() {
        // On cré les états match
        $this->createEtatsMatch();

        // On cré les phase de match
        $this->createPhasesMatch();

        // On cré les utilisateurs
        $this->createUtilisateurs();

        // On récupère toutes les équipes
        $this->createEquipes();

        // On cré les matchs
        $this->createMatchs();
    }

    private function createEtatsMatch() {

        $this->logger->addInfo("Création des états matchs");
        $this->logger->contexte="ETAT";

        foreach($this->etatsMatch as $key => $etat) {
            $objEtat = new Etat();
            $objEtat->id = $key;
            $objEtat->libelle = $etat;
            $objEtat->forcedSave();
            $this->logger->addInfo("$key: $etat");

        }
    }

    private function createPhasesMatch() {

        $this->logger->addInfo("Création des phases matchs");
        $this->logger->contexte="PHASE";
        foreach($this->phasesMatch as $key => $phase) {
            $objPhase = new Phase();
            $objPhase->id = $key;
            $objPhase->libelle = $phase;
            $objPhase->forcedSave();
            $this->logger->addInfo("$key: $phase");
        }
    }

    private function createUtilisateurs() {

        $this->logger->addInfo("Création de l'utilisateur admin");
        $objUtilisateur = new Utilisateur();
        $objUtilisateur->nom = "admin";
        $objUtilisateur->prenom = "admin";
        $objUtilisateur->login = "admin";
        $objUtilisateur->email = "admin@betfip.fr";
        $objUtilisateur->password =  "21232f297a57a5a743894a0e4a801fc3";
        $objUtilisateur->points = 0;
        $objUtilisateur->promotion = 0;
        $objUtilisateur->isactif = 1;
        $objUtilisateur->isadmin = 1;
        $objUtilisateur->save();
    }

    private function getCompetition() {

        $this->logger->debuterEtape(
            "getCompetition",
            "Récupération des informations sur la compétition"
        );

        $this->logger->contexte="COMPETITION";


        $compet = $this->api->getCompetition();
        var_dump($compet);
        $matchDay = $payload->matchday;

        $this->logger->finirEtape(
            "Récupération terminée",
            "getCompetition"
        );
    }

    private function createEquipes() {

        $this->logger->debuterEtape(
            "getEquipe",
            "Récupération des informations sur l'équipe"
        );

        $this->logger->contexte="EQUIPE";

        $equipes = $this->api->getEquipes();

        foreach($equipes as $key => $equipe) {
            $objEquipe = new Equipe();
            $objEquipe->id = $key;
            $objEquipe->code_groupe = $equipe->code_groupe;

            // On gère les noms français
            if(array_key_exists($equipe->pays, EquipeExt::$correspondancesEquipe)) {
                $objEquipe->pays = EquipeExt::$correspondancesEquipe[$equipe->pays];
            } else {
                $objEquipe->pays = $equipe->pays;
            }

            $objEquipe->forcedSave();

            $this->logger->addInfo(sprintf("%-5d: %-14s (%s)", $key, $objEquipe->pays, $equipe->code_groupe ));
        }

        $this->logger->finirEtape(
            "Récupération et création terminées",
            "getEquipe"
        );
    }

    private function createMatchs() {

        $this->logger->debuterEtape(
            "createMatchs",
            "Récupération des informations sur les matchs"
        );

        $this->logger->contexte="MATCH";

		// On récupère l'offset
		$competition = Competition::where('encours',1)->first();
		$offset = $competition->moffset;
		
		// Tant que il y a des matchs a créer
		
        $matchs = $this->api->getMatchByPhase($offset);
		
        foreach($matchs as $key => $match) {
            $equipeDom = "Non connu";
            $equipeExt = "Non connu";

            $objMatch = new Match();
            $objMatch->id = $key;

            // Gestion de la date
            $dateFormat = $this->dateService->timeToFullDate($match->date_match);
            $objMatch->date_match = $dateFormat;

            if( $match->equipe_id_dom != NULL or $match->equipe_id_ext != NULL) {
                $objMatch->equipe_id_dom = $match->equipe_id_dom;
                $objMatch->equipe_id_ext = $match->equipe_id_ext;
                $equipeDom  = $objMatch->equipe_dom()->pays;
                $equipeExt  = $objMatch->equipe_ext()->pays;
            }

            $objMatch->etat_id = $match->etat_id;
            $objMatch->score_dom = $match->score_dom;
            $objMatch->score_ext = $match->score_ext;
            $objMatch->phase_id = $match->phase_id;
            $objMatch->forcedSave();

            $phase      = $objMatch->phase->libelle;
            $etat       = $objMatch->etat->libelle;
            //~ $dateFormat = $this->dateService->timeToFullDate($objMatch->date_match);

            $this->logger->addInfo(sprintf("%d: %14s - %-14s [%-10s][%s][%s]",
                                            $key, $equipeDom, $equipeExt, $etat, $dateFormat, $phase));
        }

        $this->logger->finirEtape(
            "Récupération et création terminées",
            "createMatchs"
        );
    }

}

$populateScript = new PopulateScript();
$populateScript->run(TRUE);

exit(0);
