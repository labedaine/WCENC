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
    SELECT date_match, code_equipe_1, e1.pays as "pays1", score_equipe_1, e1.code_groupe,code_equipe_2, e2.pays as "pays2", score_equipe_2, e2.code_groupe
FROM Match m
INNER JOIN equipe e1
ON m.code_equipe_1 = e1.code_equipe
INNER JOIN equipe e2
ON m.code_equipe_2 = e2.code_equipe
WHERE e1.code_groupe = ?;
EOF;



}
