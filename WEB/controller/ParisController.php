<?php

class ParisController extends BaseController {

    private $jsonService;


    public function __construct() {
        $this->jsonService = App::make("JsonService");
    }

    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeMatch() {

      $groupe = Input::get('grp');

      $sqlQuery = self::SQL_LISTE_GROUPES;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array($groupe));
      $matchs = $stmt->fetchAll();

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
    date_match,
    equipe_id_dom,
    e1.pays as "pays1",
    score_dom,
    e1.code_groupe,
    equipe_id_ext,
    e2.pays as "pays2",
    score_ext,
    e2.code_groupe
FROM Match m
    INNER JOIN equipe e1
    ON m.equipe_id_dom = e1.id
INNER JOIN equipe e2
    ON m.equipe_id_ext = e2.id
    WHERE e1.code_groupe = ?;
EOF;



}
