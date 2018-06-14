<?php

class ParisController extends BaseController {

    private $jsonService;
    private $parisService;


    public function __construct() {
        $this->beforeFilter('authentification');
        $this->jsonService = App::make("JsonService");
        $this->parisService   = App::make("ParisService");

    }

    /**
     * Sauvegarde les paris de l'utilisateur
     */
    public function sauvegarderParis() {
      $listParis = Input::get('listParis');
      $user = SinapsApp::utilisateurCourant()->id;

      foreach ($listParis as $key => $unParis) {
        $listParis[$key] = $this->parisService->sauvegarderParis($user, $unParis->id,  $unParis->scoreDom, $unParis->scoreExt);
      }

      return JsonService::createResponse($listParis);
    }
    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeMatch() {

      // Valeur par défaut groupe A
      $groupe = Input::get('grp', "1");

      //~ if (preg_match("/[A-H]/", $groupe))
      //~ {
        //~ $sqlQuery = self::SQL_LISTE_GROUPES;
      //~ }
      if (!preg_match("/^1|2|3|4|5|6|7|8$/", $groupe)) {
          $groupe = "1";
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
      }

      return JsonService::createResponse($matchs);

      // $listeMatch = Match::all();
      // //Match::where('id', '=', 'o')->get();
      // $listeUsers = array();
      // foreach ($mesUsers as $user) {
      //     $tmp = $user->toArray();
      //     unset($tmp->password);
      //     $listeUsers[] = $tmp;
      // }


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



}
