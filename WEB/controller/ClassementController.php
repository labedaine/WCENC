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

      $type = Input::get('promo');

      $sqlQuery = self::SQL_GET_CLASSEMENT;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $matchs = $stmt->fetchAll();

      return JsonService::createResponse($matchs);


    }

    const SQL_GET_CLASSEMENT = <<<EOF
    SELECT promotion, points, prenom, nom, login
    FROM utilisateur
    WHERE promotion != 0
    ORDER BY points DESC;
EOF;
    const SQL_GET_CLASSEMENT_PROMO = <<<EOF
    SELECT promotion, SUM(points) as total, COUNT(id) as nb, SUM(points) / COUNT(id) as moyenne
    FROM utilisateur
    WHERE promotion != 0
    GROUP BY promotion
    ORDER BY moyenne DESC;
EOF;


}
