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

    public function __construct() {
        parent::__construct(__DIR__."/../config","ApiLogger","api");
        $this->logger = SinapsApp::make("ApiLogger");

        $this->restClientService    = SinapsApp::make("RestClientService");
        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");
    }

    public function configure() {
        $conf = $this->setName(__FILE__)
                     ->setDescription("Va chercher les infos sur le site api.football-data.org")

                     ->addOption(
                         "competition",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "Retourne les informations relatives à la compétition"
                     )

                     //
                     ->startAlternative()
                     ->addOption(
                         "match",
                         SinapsScript::OBLIGATOIRE,
                         "Retourne les informations relatives à un match"
                     )

                     //
                    ->startAlternative()
                    ->addOption(
                        "equipe",
                        SinapsScript::OBLIGATOIRE,
                        "Retourne les informations relatives à une équipe"
                    );
    }

    public function performRun() {
        try {

            if(isset($this->options->competition)) {
                $this->getCompetition();
            }
            /*if(isset($this->options->stringToIdDerogation)) {
                $this->corrigeChampsDerogation();
            }
            if(isset($this->options->liste)) {
                $this->afficherListe();
            }*/


        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }

    }

    private function getCompetition() {

        $this->logger->debuterEtape(
            "getCompetition",
            "Récupération des informations sur la compétition"
        );

        $this->logger->contexte="COMPETITION";
        $this->logger->addInfo="on teste";

        // http://api.football-data.org/v1/competitions/467/leagueTable

        $maintenant = $this->timeService->now();

        $this->url     = SinapsApp::getConfigValue("api.competition");

        $url = "http://" . $this->srvApp . $this->url;
        $param = array();

        $status = array();
        try {

            $json = $this->restClientService->getURL($url, $param, FALSE, $this->timeoutCurl);
            $this->restClientService->throwExceptionOnError($json);

        } catch (SinapsException $exc) {
            return $exc->getCode();
        }

        $response = json_decode($json, FALSE);
        var_dump($response);

        $this->logger->finirEtape(
            "Récupération terminée",
            "getCompetition"
        );
    }

}

$curlApiScript = new CurlApiScript();
$curlApiScript->run(TRUE);

exit(0);
