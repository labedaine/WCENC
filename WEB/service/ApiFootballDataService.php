<?php
/**
 * Classe ApiFootballDataService.php.
 * Sert à récupérer tous ce qui est sujet à la WC2018 sur le site football-data
 *
 * PHP Version 5
 *
 * @author dgfip <dgfip@dgfip.finances.gouv.fr>
 */

class ApiFootballDataService {

    protected $jsonService;
    protected $timeService;
    protected $dateService;

    private $srv = NULL;
    private $timeoutCurl = NULL;
    private $apiKey = NULL;

    public function __construct() {

        $this->restClientService    = SinapsApp::make("RestClientService");
        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");

        // Timeout
        $this->timeoutCurl       = SinapsApp::getConfigValue("api.timeout");

        // Intialisation des variables communes
        $this->init();
    }

    private function init() {

        $this->srv = SinapsApp::getConfigValue("api.url");
        $this->timeoutCurl = SinapsApp::getConfigValue("api.timeout");
        $this->apiKey = SinapsApp::getConfigValue("api.key");
    }

    /**
     * Récupère un objet json en lui donnant une url
     */

    private function getFromAPI($url) {

        $url = "http://" . $this->srv . $url;
        $param = array();
        $status = array();

        try {
            $json = $this->restClientService->getURL($url, NULL, FALSE, $this->timeoutCurl, $this->apiKey);
            $this->restClientService->throwExceptionOnError($json);

        } catch (SinapsException $exc) {
            return $exc->getCode();
        }

        $response = json_decode($json, FALSE);
        $payload = json_decode($response->payload, FALSE);
        return $payload;
    }

    public function getTableau() {
        $retour = $this->getFromAPI(SinapsApp::getConfigValue("api.classement"));

        // On supprime tout ce qui ne sert pas
        return $retour;
    }

    /**
     * Renvoie les infos propres à la compétition
     */

    public function getCompetition() {

        $retour = $this->getFromAPI(SinapsApp::getConfigValue("api.competition"));

        // On supprime tout ce qui ne sert pas
        return $retour;
    }

    /**
     * Renvoie les infos propres aux équipes
     */

    public function getEquipes() {

        $equipes = array();

        $retour = $this->getFromAPI(SinapsApp::getConfigValue("api.classement"));

        // On supprime tout ce qui ne sert pas
        if(isset($retour->standings)) {

            foreach($retour->standings as $groups) {

                foreach($groups as $allEquipes) {

                    // Chaque équipe
                    foreach($allEquipes as $equipe) {
						$objEquipe = new stdClass();
						$objEquipe->code_groupe = substr($groups->group, -1);
						$objEquipe->pays = $equipe->team->name;
						$equipes[$equipe->team->id] = $objEquipe;
					}
                }
            }
        } else {
            return JsonService::createErrorResponse("Aucune équipe trouvée");
        }
        return $equipes;
    }

    /**
     * Ne retourne qu'un match
     */

    public function getMatchById($idMatch) {
        $url = sprintf(SinapsApp::getConfigValue("api.match.id"), $idMatch);
        return $this->getMatchs($url);
    }

     /**
     * Ne retourne que les matchs d'une phase
     */

    public function getMatchByPhase($idPhase) {
        $url = sprintf(SinapsApp::getConfigValue("api.match.phase"), $idPhase);
        return $this->getMatchs($url);
    }

    /**
     *  Permet de renvoyer un ou des matchs
     *  fixtures
     */

    public function getMatchs($url=NULL) {

        if(!$url) {
            $url = SinapsApp::getConfigValue("api.match");
        }
        $retourMatchs = array();

        $retour = $this->getFromAPI($url);
        // On ne garde que ce qui sert

        // On a qu'un objet retourné ?
        if(isset($retour->matches)) {
            $matchs = $retour->matches;

        } else if(isset($retour->match)) {
            $matchs = array($retour->match);

        } else {
            return NULL;
        }

        foreach($matchs as $match) {

            $objMatch = new stdClass();

            //~ $urlFixture     = $match->_links->self->href;
            //~ $urlHomeTeam    = $match->_links->homeTeam->href;
            //~ $urlAwayTeam    = $match->_links->awayTeam->href;

            // Equipes
            $objMatch->equipe_id_dom = $match->homeTeam->id;
            $objMatch->equipe_id_ext = $match->awayTeam->id;

            // Etat
            $etat = Etat::where('libelle', $match->status)->first();
            if(!$etat) {
                $objMatch->etat_id = 2;
            } else {
                $objMatch->etat_id = $etat->id;
            }

            // Score
            $objMatch->score_dom = $match->score->fullTime->homeTeam;
            $objMatch->score_ext = $match->score->fullTime->awayTeam;

            /** TODO
             * "result": {
                    "goalsHomeTeam": 1
                    "goalsAwayTeam": 1
                    "halfTime": {
                        "goalsHomeTeam": 1
                        "goalsAwayTeam": 0
                    }
                    "extraTime": {
                        "goalsHomeTeam": 1
                        "goalsAwayTeam": 1
                    }
                    "penaltyShootout": {
                        "goalsHomeTeam": 5
                        "goalsAwayTeam": 3
                    }
                }
            */

            // Phase
            $objMatch->phase_id = $match->matchday;

            // Date
            $objMatch->date_match = strtotime($match->utcDate);

            $retourMatchs[$match->id] = $objMatch;
        }

        if(count($retourMatchs) == 1) {
            return array_pop($retourMatchs);
        }

        return $retourMatchs;
    }



    /*
    //$sqlQuery = str_replace('__LOGIN__', SinapsApp::utilisateurCourant()->login, $sqlQuery);
    //print $sqlQuery;
    $stmt = $this->dbh->prepare($sqlQuery);

    // print "<pre>$sqlQuery</pre><hr>";
    $stmt->bindValue('loginutilisateur', SinapsApp::utilisateurCourant()->login);
    $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "AlerteDTO");
    return $stmt;


    $retour = $this->jsonService->createResponse($alerteId);
    return $retour;*/


/**
 * Clause WHERE 'LES ALERTES GERES PAR L'UTILISATEUR'
 * Vient en complément de la requête de base "self::SQL_CLAUSE_SELECT"
 */
const SQL_WHERE_ALERTES_DE_UTILISATEUR = <<<EOF
    utilisateur.login = alerte."loginUtilisateurEnCharge"
EOF;

const SQL_WHERE_ALERTES_PRISES_EN_COMPTE = <<<EOF
    alerte."statutAlerte" = 2
EOF;



}
