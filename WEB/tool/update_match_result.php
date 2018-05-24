#!/usr/bin/php
<?php
/**
 * Script pour enregistrer la correspondance entre une application interne (Application.
 *
 * Et une application externe (EvenementExterieurApplication)
 * Par défaut, le script requiert l'existence préalable des 2 applications.
 * Néanmoins, il est possible en évoquant l'arguemnt --force de passer outre ces vérifications
 *
 * PHP version 5
 *
 * @author Stéphane Gatto <stephane.gatto@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../Autoload.php";
require_once __DIR__."/../ressource/php/Autoload.php";

class CurlApiScript extends SinapsScript {

    private $test = FALSE;

    public function __construct() {

        parent::__construct(__DIR__."/../config","ApiLogger","api");
        $this->logger = SinapsApp::make("ApiLogger");

        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");
        $this->restClientService    = SinapsApp::make("RestClientService");

        $this->api = new ApiFootballDataService();
    }

    public function configure() {
        $conf = $this->setName(__FILE__)
                     ->setDescription("Va chercher les infos sur le site api.football-data.org")

                     ->addOption(
                         "--update",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "Met à jour en base de données suivant "
                     )

                     //
                     ->startAlternative()
                     ->addOption(
                         "--show",
                         SinapsScript::OBLIGATOIRE| SinapsScript::VALUE_NONE,
                         "Affiche les matches devant être mis à jour"
                     );
    }

    public function performRun() {
        try {

            // On récupère les données de match à mettre à jour
            $this->getMatchDeLaVeille();

            // Si on update on met à jour la base
            if(isset($this->options->update)) {
                $this->update(TRUE);
            }

            // Sinon on montre juste
            if(isset($this->options->show)) {
                $this->update(FALSE);
            }

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }

    private function getMatchDeLaVeille() {
        $now = $this->timeService->now();


    }

    private function getCompetition() {

        $this->logger->debuterEtape(
            "getCompetition",
            "Récupération des informations sur la compétition"
        );

        $this->logger->contexte="COMPETITION";

        $competition = $this->api->getCompetition();
        var_dump($competition);
        $matchDay = $competition->currentMatchday;

        $this->logger->finirEtape(
            "Récupération terminée",
            "getCompetition"
        );
    }

    private function getEquipe() {

        $this->logger->debuterEtape(
            "getEquipe",
            "Récupération des informations sur l'équipe"
        );

        $this->logger->contexte="EQUIPE";

        $equipes = $this->api->getEquipes();
        var_dump($equipes);
        $this->logger->finirEtape(
            "Récupération terminée",
            "getEquipe"
        );
    }

    private function getMatchs($idMatch) {
        $this->logger->debuterEtape(
            "getMatch",
            "Récupération des informations sur un match"
        );

        $this->logger->contexte="MATCH";

        $match = $this->api->getMatchById($idMatch);
        var_dump($match);
        $this->logger->finirEtape(
            "Récupération terminée",
            "getMatch"
        );
    }
}

$curlApiScript = new CurlApiScript();
$curlApiScript->run(TRUE);

exit(0);
