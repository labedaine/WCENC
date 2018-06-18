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
        $matchs['collec'][$key]['moyenne'] = round($matchs['collec'][$key]['total'] / $matchs['collec'][$key]['nb'], 2);
      }

      usort($matchs['collec'], function($a, $b) {
        return $a['moyenne'] < $b['moyenne'];
      });

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
    SELECT promotion, points, prenom, nom, login, id
    FROM utilisateur
    WHERE promotion != 0
    ORDER BY points DESC;
EOF;

    const SQL_GET_CLASSEMENT_PROMO = <<<EOF
    SELECT
        u.promotion,
        SUM(p.points_acquis) as total,
        COUNT(p.id) as nb,
        0 as moyenne
    FROM utilisateur u
    JOIN paris p ON p.utilisateur_id = u.id
    JOIN match m ON p.match_id = m.id
    WHERE u.promotion != 0
    AND m.etat_id = 6
    GROUP BY u.promotion
    ORDER BY moyenne DESC;
EOF;


}
