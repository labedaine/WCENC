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

    const DEBUT_PHASE_FINALE=4;

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
            //$this->getMatchDansLHeure();
            $this->majPhaseFinale();

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
        $now = 1529145000;

        // Les matches qui ont déja commencé
        $matchsDansLH  = Match::where('date_match', '>', $this->dateService->timeToUS($now-3600))
                              ->where('date_match', '<', $this->dateService->timeToUS($now))
                              ->get();

        // Si on a récupéré une liste de match,
        // on va chercher pour chacun ses infos
        if(!empty($matchsDansLH)) {

            $this->logger->debuterEtape(
                "majMatch",
                "Récupération des informations sur la compétition"
            );

            foreach($matchsDansLH as $match) {

                $this->logger->contexte=$match->id;

                // Récupération des données
                $infoMatch = $this->api->getMatchById($match->id);

                // On regarde si le match à un status différent de celui en base
                if($match->etat_id != $infoMatch->etat_id) {
                    $libEtatOld = $match->etat->libelle;
                    $objEtatNew = Etat::find($infoMatch->etat_id)->first();
                    $libEtatNew = $objEtatNew->libelle;

                    $this->logger->addInfo(sprintf("état %s => %s",
                                                   $libEtatOld, $libEtatNew));

                    $match->etat_id = $infoMatch->etat_id;
                }

                // On regarde si le score est différent de celui en base
                if(($match->score_dom != $infoMatch->score_dom) ||
                   ($match->score_ext != $infoMatch->score_ext)) {

                    $this->logger->addInfo(sprintf("score %d - %d => %d - %d",
                                                   $match->score_dom, $match->score_ext,
                                                   $infoMatch->score_dom, $infoMatch->score_ext));


                    $match->score_dom = $infoMatch->score_dom;
                    $match->score_ext = $infoMatch->score_ext;
                }

                if(count($match->dirty) > 0) {
                    $match->save();
                    $this->logger->addInfo("Sauvegarde du match effectuée avec succès");
                }

            }
            $this->logger->finirEtape(
                "Mise à jour terminée",
                "majMatch"
            );
        }
    }

    private function update($up=FALSE) {

    }

    private function majPhaseFinale($update=FALSE) {
        $matchDay = $this->getCompetition();

        // Ne fonctionne que pour les phases finales
        //if($matchDay >= CurlApiScript::DEBUT_PHASE_FINALE) {

            // On récupère les matchs de la phase en cours
            // dont les équipes ne sont pas remplis
            $phase = Phase::find($matchDay);
            $matchsDeLaPhase = $this->api->getMatchByPhase($phase);

            // On ne récupère que ceux qui n'ont pas encore été mis à jour
            $matchsFiltre = array_filter($matchs, function($element) {
                                var_dump($element);
                                if($element->equipe_id_dom === NULL)
                                    return 1;
                                if($element->equipe_id_ext === NULL)
                                    return 1;
                                return 0;
                            });

            // On recherche maintenant le match correspondant pour trouver les équipes mise à jour
            foreach($matchsDeLaPhase as $match) {
                $objMatch = Match::find($match->id);

                // Si on a des valeurs donnée par l'API
                if($match->equipe_id_dom !== NULL && $match->equipe_id_ext !== NULL) {

                    // Si c'est valeur sont à mettre à jour
                    if($objMatch->equipe_id_dom === NULL && $objMatch->equipe_id_ext === NULL) {
                        $objMatch->equipe_id_dom = $match->equipe_id_dom;
                        $objMatch->equipe_id_ext = $match->equipe_id_ext;

                        if(1) {
                            $objMatch->save();
                            $this->logger->addInfo("Sauvegarde du match effectuée avec succès");
                        }
                    }
                }
            }
        //}
    }

    private function getCompetition() {

        $this->logger->debuterEtape(
            "getCompetition",
            "Récupération des informations sur la compétition"
        );

        $this->logger->contexte="COMPETITION";

        $competition = $this->api->getCompetition();
        return $competition->currentMatchday;

        $this->logger->finirEtape(
            "Récupération terminée",
            "getCompetition"
        );
    }
}

$curlApiScript = new CurlApiScript();
$curlApiScript->run(TRUE);

exit(0);
