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
                         "update",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "Met à jour en base de données suivant "
                     )

                     //
                     ->startAlternative()
                     ->addOption(
                         "no_update",
                         SinapsScript::OBLIGATOIRE| SinapsScript::VALUE_NONE,
                         "Affiche les matches devant être mis à jour"
                     );
    }

    public function performRun() {
        try {

            // On récupère les données de match à mettre à jour
            $this->getMatchDansLHeure();

            // Si on update on met à jour la base
            if(isset($this->options->update)) {
                $this->update(TRUE);
            }

            // Sinon on montre juste
            if(isset($this->options->no_update)) {
                $this->update(FALSE);
            }

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }

    private function getMatchDansLHeure() {
        $now = $this->timeService->now();
        $now = 1529141812;

        $matchsDansLH = Match::where('date_match', '>', $this->dateService->timeToUS($now))
                             ->where('date_match', '<', $this->dateService->timeToUS($now+3600))
                             ->get();

        var_dump($matchsDansLH);

        // Si on a récupéré une liste de match,
        // on va chercher pour chacun ses infos
        if(!empty($matchsDansLH)) {
            foreach($matchsDansLH as $match) {

                // Récupération des données
                $infoMatch = $this->api->getMatchById($match->id);

                // On regarde si le match à un status différent de celui en base
                if($match->etat_id != $infoMatch->etat_id) {
                    $libEtatOld = $match->etat->libelle;
                    $objEtatNew = Etat::find($infoMatch->etat_id)->first();
                    $libEtatNew = $objEtatNew->libelle;

                    $this->logger->addInfo(sprintf("%d: état %s => %s",
                                                    $match->id, $libEtatOld, $libEtatNew));

                    $match->etat_id = $infoMatch->etat_id;
                }

                // On regarde si le score est différent de celui en base
                if(($match->score_dom != $infoMatch->score_dom) ||
                   ($match->score_ext != $infoMatch->score_ext)) {

                    $this->logger->addInfo(sprintf("%d: score %d - %d => %d - %d",
                                                    $match->id,
                                                    $match->score_dom, $match->score_ext,
                                                    $infoMatch->score_dom, $infoMatch->score_ext));


                    $match->score_dom = $infoMatch->score_dom;
                    $match->score_ext = $infoMatch->score_ext;
                }

                var_dump($match);
            }
        }
    }

    private function update($update=FALSE) {

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
}

$curlApiScript = new CurlApiScript();
$curlApiScript->run(TRUE);

exit(0);
