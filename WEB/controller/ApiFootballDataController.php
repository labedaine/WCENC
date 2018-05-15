<?php
/**
 * Classe ResultatController.php.
 * Sert à récupérer les résultats des matchs
 *
 * PHP Version 5
 *
 * @author dgfip <dgfip@dgfip.finances.gouv.fr>
 */

class ApiFootballDataController extends BaseController {

    protected $jsonService;
    protected $timeService;
    protected $dateService;

    public function __construct() {

        $this->restClientService    = SinapsApp::make("RestClientService");
        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");

        // Timeout
        $this->timeoutCurl       = SinapsApp::getConfigValue("api.timeout");
    }

    /**
     * Renvoie les infos propres à la compétition
     */

    public function getCompetition() {
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
