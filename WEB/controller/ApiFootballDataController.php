<?php
/**
 * Controller utilisé par le script de mise à jour
 *
 *
 * PHP version 5
 *
 * @author Damien andré <damien.andre@dgfip.finances.gouv.fr>
 */

class ApiFootballDataController extends BaseController {

    private $test = FALSE;
    private $now = 0;
    private $parisService;
    public $phaseEnCours = 1;

    const DEBUT_PHASE_FINALE=7;

    public function __construct() {

        //SinapsApp::initialise(__DIR__."/../config");

        SinapsApp::registerLogger("ApiLogger", "api");
        $this->logger = SinapsApp::make("ApiLogger");

        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");
        $this->restClientService    = SinapsApp::make("RestClientService");
        $this->parisService     = SinapsApp::make("ParisService");

        $this->api = new ApiFootballDataService();

        $this->now = $this->timeService->now();
    }

    public function miseAJourMatchDansLHeure() {

        // Les matches qui ont déja commencé (fenêtre de trois heures)
        $matchsDansLH  = Match::where('date_match', '>', $this->dateService->timeToUS($this->now-10800))
                               ->where('date_match', '<', $this->dateService->timeToUS($this->now))
                               ->orderBy('date_match', 'ASC')
                               ->get();
                               
        //$matchsDansLH = Match::whereIn('etat_id', array(1,2,5))->get();
        // Si on a récupéré une liste de match,
        // on va chercher pour chacun ses infos
        $this->logger->addInfo(sprintf("%d matchs trouvés", count($matchsDansLH)));

        if(!empty($matchsDansLH)) {

            $this->logger->debuterEtape(
                "majMatch",
                "Récupération des informations sur la compétition"
            );

            foreach($matchsDansLH as $match) {

                $this->logger->contexte=$match->id;

                // Récupération des données
                $infoMatch = $this->api->getMatchById($match->id);
                
                if($infoMatch !== NULL) {
                    // On regarde si le match à un status différent de celui en base
                    if($match->etat_id != $infoMatch->etat_id) {
                        $libEtatOld = $match->etat->libelle;
                        $objEtatNew = Etat::find($infoMatch->etat_id);
                        $libEtatNew = $objEtatNew->libelle;

                        $this->logger->addInfo(sprintf("état %s => %s",
                                                       $libEtatOld, $libEtatNew));

                        $match->etat_id = $infoMatch->etat_id;
                    }

                    // On regarde si le score est différent de celui en base
                    if(($match->score_dom !== $infoMatch->score_dom) ||
                       ($match->score_ext !== $infoMatch->score_ext)) {

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

                     // on lance le calcul des points acquis pour tous les paris du match
                     $this->parisService->calculerPointsParis($match->id);
                } else {
					$this->logger->addInfo("aucun match trouvé");
				}

            }

            // on met a jours le nombre de points acquis pour tous utilisateurs
            $this->parisService->miseAJourPointsUtilisateurs();

            $this->logger->finirEtape(
                "Mise à jour terminée",
                "majMatch"
            );
        }
    }

    public function update($up=FALSE) {

    }

    public function majPhaseFinale($update=FALSE) {

        // Mise à jour de la phase d'apres

        // Ne fonctionne que pour les phases finales
        if($this->phaseEnCours >= ApiFootballDataController::DEBUT_PHASE_FINALE) {

            // On récupère les matchs de la phase en cours
            // dont les équipes ne sont pas remplis
            $phase = Phase::find($this->phaseEnCours);
            $matchsDeLaPhase = $this->api->getMatchByPhase($phase->id);

            if($matchsDeLaPhase === NULL) {
                $this->logger->addError("L'api distante n'est pas disponible");
                return;
            }

            // On ne récupère que ceux qui n'ont pas encore été mis à jour
            $matchsFiltre = array_filter($matchsDeLaPhase, function($element) {

                                if($element->equipe_id_dom === NULL)
                                    return 1;
                                if($element->equipe_id_ext === NULL)
                                    return 1;
                                return 0;
                            });
            // On recherche maintenant le match correspondant pour trouver les équipes mise à jour
            foreach($matchsDeLaPhase as $idMatch => $match) {

                $objMatch = Match::find($idMatch);

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
        } else {
            $this->logger->addInfo("Date des phases finales non atteinte");
        }
    }

    public function setPhaseEnCours($log=TRUE) {
        if($log) {
            $this->logger->debuterEtape(
                "getCompetition",
                "Récupération des informations sur la compétition"
            );

            $this->logger->contexte="COMPETITION";
        }
        $competition = Competition::where('encours',1)
								  ->first();

        $this->phaseEnCours = 0;

        if(isset($competition->cmatchday)) {
            $this->phaseEnCours = $competition->cmatchday;
            $phase = Phase::find($this->phaseEnCours);

            // Si les matchs de la phase sont tous terminés on passe à la pahse suivante
            $nbMatchAJouer = Match::where('phase_id', $phase->id)
                                  ->where('etat_id' ,'<>', 6)
                                  ->count();
            if($nbMatchAJouer != 0) {
                if($log)
                $this->logger->addInfo("La phase en cours est en cours [" . $phase->libelle . "[ (". $this->phaseEnCours .")");

            } else {
                $this->phaseEnCours = $this->phaseEnCours+1;
                $phase = Phase::find($this->phaseEnCours);
                if($log)
                $this->logger->addInfo("La phase en cours est terminée. La prochaine est [" . $phase->libelle . "] (". $this->phaseEnCours .")");
            }

        }
        if($log)
        $this->logger->finirEtape(
            "Récupération terminée",
            "getCompetition"
        );
    }
}
