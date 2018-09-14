<?php

class ParisController extends BaseController {

    private $jsonService;
    private $parisService;


    public function __construct() {
        $this->beforeFilter('authentification');
        $this->jsonService = App::make("JsonService");
        $this->parisService   = App::make("ParisService");
        $this->timeService   = App::make("TimeService");

        // On récupère la phase en cours
        $this->apiController = new ApiFootballDataController();
        $this->apiController->setPhaseEnCours(FALSE);


    }

    /**
     * Sauvegarde les paris de l'utilisateur
     */
    public function sauvegarderParis() {
      $listParis = Input::get('listParis');
      $user = SinapsApp::utilisateurCourant()->id;

      foreach ($listParis as $key => $unParis) {
		if(isset($unParis->id)) {
			$listParis[$key] = $this->parisService->sauvegarderParis($user, $unParis->id,  $unParis->scoreDom, $unParis->scoreExt);
		}
        
      }

      return JsonService::createResponse($listParis);
    }
    
    /**
     * Sauvegarde le pronostic du vainqueur de la compét
     */
    public function sauvegarderProno() {

      $idTeam = Input::get('idEquipe');
      $user = SinapsApp::utilisateurCourant()->id;

      $retour = $this->parisService->sauvegarderWinnerCompet($user, $idTeam);

      return JsonService::createResponse($retour);
    }

    /**
     * Récupère le vainqueur pour l'utilisateur
     */

    public function getVainqueur() {
		
		$retour = array("winner" => "");
		
		$user = SinapsApp::utilisateurCourant()->id;
		
		// Est ce qu'il y a une competition en cours
		// Normalement il n'y en a qu'une ...
		$compet = Competition::where('encours' , 1)->first();

		if($compet == NULL) {			  
			throw new Exception("Aucune compétition n'est active.");
		}

		$match = Match::where()->orderBy('date_match')->first();
		if($match == NULL) {
			throw new Exception("Aucun match n'est programmé.");
		}
		
		$vainqueur = Pronostic::where('utilisateur_id', $user)
								->where('competition_id', $compet->id)
								->first();
		if($vainqueur == NULL) {
			return JsonService::createResponse(false);
		}
		
		// on cherche l'equipe correspondante
		$equipe = Equipe::find($vainqueur->equipe_id);
		
		return JsonService::createResponse(array($equipe->id => $equipe->pays));
	}
	
	/**
     * Récupère les equipes en base
     */
    public function getTeamInBDD() {
		
		$retour = array();

		
		// Est ce qu'il y a une competition en cours
		// Normalement il n'y en a qu'une ...
		$equipes = Equipe::all();

		foreach($equipes as $equipe) {
			array_push($retour, array($equipe->id => $equipe->pays));
		}
		
		return JsonService::createResponse($retour);
	}
	
	
    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeMatch() {

      // Valeur par défaut groupe A
      $groupe = Input::get('grp', $this->apiController->phaseEnCours);

      //~ if (preg_match("/[A-H]/", $groupe))
      //~ {
        //~ $sqlQuery = self::SQL_LISTE_GROUPES;
      //~ }
      if (!preg_match("/^(1|2|3|4|5|6|7|8|9)+$/", $groupe)) {
          $groupe = $this->apiController->phaseEnCours;
      }

      $sqlQuery = self::SQL_LISTE_MATCH_PHASE;

      $user = SinapsApp::utilisateurCourant()->id;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array('groupe' => $groupe, 'id' => $user));
      $matchs = $stmt->fetchAll();

      foreach($matchs as $key => $match) {
          $matchs[$key]['etat'] = MatchExt::$etatsMatch[$match["etat_id"]];
          $matchs[$key]['past'] = ($match['date_match'] < date("Y-m-d 00:00:00", $this->timeService->now()) ? 1 : 0);
      }

      return JsonService::createResponse($matchs);
    }

    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeParisUser() {

      // Valeur par défaut groupe A
      $userId = Input::get('userId');

      $sqlQuery = self::SQL_LISTE_PARIS_AUTRE;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array( 'id' => $userId));
      $paris = $stmt->fetchAll();

      return JsonService::createResponse($paris);
    }

    const SQL_LISTE_GROUPES = <<<EOF
    SELECT
        m.id as "id",
        date_match,
        equipe_id_dom,
        e1.pays as "pays1",
        m.score_dom as "score_dom",
        e1.code_groupe,
        equipe_id_ext,
        e2.pays as "pays2",
        m.score_ext as "score_ext",
        e2.code_groupe,
        p.score_dom as "paris_dom",
        p.score_ext as "paris_ext",
        etat_id
    FROM Match m
        INNER JOIN equipe e1
        ON m.equipe_id_dom = e1.id
    INNER JOIN equipe e2
        ON m.equipe_id_ext = e2.id
    LEFT JOIN paris p
        ON p.match_id = m.id
        AND p.utilisateur_id = :id
        WHERE e1.code_groupe = :groupe;
EOF;

    const SQL_LISTE_MATCH_PHASE = <<<EOF
    SELECT
        m.id as "id",
        date_match,
        equipe_id_dom,
        e1.pays as "pays1",
        m.score_dom as "score_dom",
        e1.code_groupe as "groupe1",
        equipe_id_ext,
        e2.pays as "pays2",
        m.score_ext as "score_ext",
        e2.code_groupe as "groupe2",
        p.score_dom as "paris_dom",
        p.score_ext as "paris_ext",
        p.points_acquis,
        etat_id
    FROM Match m
    LEFT JOIN equipe e1
        ON m.equipe_id_dom = e1.id
    LEFT JOIN equipe e2
        ON m.equipe_id_ext = e2.id
    LEFT JOIN paris p
        ON p.match_id = m.id
        AND p.utilisateur_id = :id
        WHERE m.phase_id = :groupe
    ORDER BY m.date_match ASC;
EOF;

    const SQL_LISTE_PARIS_AUTRE = <<<EOF
    SELECT
        ph.libelle,
        m.phase_id,
        m.id as "id",
        e1.pays as "pays1",
        m.score_dom as "score_dom",
        e2.pays as "pays2",
        m.score_ext as "score_ext",
        p.score_dom as "paris_dom",
        p.score_ext as "paris_ext",
        p.points_acquis,
        etat_id
    FROM Match m
    LEFT JOIN equipe e1
        ON m.equipe_id_dom = e1.id
    LEFT JOIN phase ph
        ON m.phase_id = ph.id
    LEFT JOIN equipe e2
        ON m.equipe_id_ext = e2.id
    LEFT JOIN paris p
        ON p.match_id = m.id
        AND p.utilisateur_id = :id
    WHERE m.etat_id = 6
    ORDER BY m.date_match DESC;
EOF;

}
