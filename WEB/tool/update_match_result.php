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

        $this->apiController = new ApiFootballDataController();
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

            $this->apiController->setPhaseEnCours();
            $this->apiController->miseAJourMatchDansLHeure();
            $this->apiController->majPhaseFinale();

            // Si on update on met à jour la base
            if(isset($this->options->update)) {
                $this->apiController->update(TRUE);
            }

            // Sinon on montre juste
            if(isset($this->options->no_update)) {
                $this->apiController->update(FALSE);
            }

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }
}

$curlApiScript = new CurlApiScript();
$curlApiScript->run(TRUE);

exit(0);
