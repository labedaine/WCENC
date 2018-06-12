<?php

class ClassementController extends BaseController {

    private $jsonService;


    public function __construct() {
        $this->jsonService = App::make("JsonService");
    }

    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListeClassement() {

      $sqlQuery = self::SQL_GET_CLASSEMENT;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $matchs['indiv'] = $stmt->fetchAll();



      $sqlQuery = self::SQL_GET_CLASSEMENT_PROMO;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $matchs['collec'] = $stmt->fetchAll();

      foreach ($matchs['collec'] as $key => $value) {
        //echo UtilisateurExt::numToString($value['promotion']);
        $matchs['collec'][$key]['promotion'] = UtilisateurExt::numToString($value['promotion']);
      }

      foreach ($matchs['indiv'] as $key => $value) {

        //echo UtilisateurExt::numToString($value['promotion']);
        $matchs['indiv'][$key]['promotion'] = UtilisateurExt::numToString($value['promotion']);
        $value['promotxt'] = UtilisateurExt::numToString($value['promotion']);
        $matchs['promo'][$value['promotion']][] = $value;
      }


      return JsonService::createResponse($matchs);
    }

    /**
     *
     */
    public function getListeClassementCollectif() {

      $sqlQuery = self::SQL_GET_CLASSEMENT_PROMO;

      $dbh = SinapsApp::make("dbConnection");
      $stmt = $dbh->prepare($sqlQuery);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $matchs['collec'] = $stmt->fetchAll();

      foreach ($matchs['collec'] as $key => $value) {
        //echo UtilisateurExt::numToString($value['promotion']);
        $matchs['collec'][$key]['promotion'] = UtilisateurExt::numToString($value['promotion']);
      }

      return JsonService::createResponse($matchs);
    }

    /**
     *
     */
    public function getListeClassementIndiv() {


            $sqlQuery = self::SQL_GET_CLASSEMENT;

            $dbh = SinapsApp::make("dbConnection");
            $stmt = $dbh->prepare($sqlQuery);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();
            $matchs['indiv'] = $stmt->fetchAll();



            foreach ($matchs['indiv'] as $key => $value) {

              //echo UtilisateurExt::numToString($value['promotion']);
              $matchs['indiv'][$key]['promotion'] = UtilisateurExt::numToString($value['promotion']);

            }


            return JsonService::createResponse($matchs);
    }

    /**
     *
     */
    public function getListeClassementPromo() {

            $sqlQuery = self::SQL_GET_CLASSEMENT;

            $dbh = SinapsApp::make("dbConnection");
            $stmt = $dbh->prepare($sqlQuery);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();
            $matchs['indiv'] = $stmt->fetchAll();

            foreach ($matchs['indiv'] as $key => $value) {
              $matchs['indiv'][$key]['promotion'] = UtilisateurExt::numToString($value['promotion']);
              $value['promotxt'] = UtilisateurExt::numToString($value['promotion']);
              $matchs['promo'][$value['promotion']][] = $value;
            }

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
