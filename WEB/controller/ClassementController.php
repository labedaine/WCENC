<?php

class ClassementController extends BaseController {

    private $jsonService;


    public function __construct() {
        $this->jsonService = App::make("JsonService");
    }

    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeClassment() {

      $type = Input::get('type');

      $sqlQuery = self::SQL_GET_CLASSEMENT;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array($type));
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

    const SQL_GET_CLASSEMENT = <<<EOF
    SELECT date_match, code_equipe_1, e1.pays as "pays1", score_equipe_1, e1.code_groupe,code_equipe_2, e2.pays as "pays2", score_equipe_2, e2.code_groupe
FROM Match m
INNER JOIN equipe e1
ON m.code_equipe_1 = e1.code_equipe
INNER JOIN equipe e2
ON m.code_equipe_2 = e2.code_equipe
WHERE e1.code_groupe = ?;
EOF;



}
