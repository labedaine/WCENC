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



      foreach($matchs['indiv'] as $key => $match) {

            $pari3 = 0;
            $pari2 = 0;
            $pari1 = 0;
           for($i=1;$i++;$i<8) {

                $sqlQuery = self::SQL_GET_ALL_MATCH_ID_BY_PHASE;

                $dbh = SinapsApp::make("dbConnection");
                $stmt = $dbh->prepare($sqlQuery);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute(array( 'phase' => $i));
                $matchByPhase = $stmt->fetchAll();
var_dump($matchByPhase);
                $coeff = $i-2;
                if($coeff <= 1 ) $coeff=1;

                $pari3 += Paris::where('points_acquis', 3*$coeff)
                                ->where('utilisateur_id', $match['id'])
                                ->whereIn('match_id', $matchByPhase)
                                ->count();
                $pari2 += Paris::where('points_acquis', 2*$coeff)
                               ->where('utilisateur_id', $match['id'])
                                ->whereIn('match_id', $matchByPhase)
                               ->count();
                $pari1 += Paris::where('points_acquis', 1*$coeff)
                               ->where('utilisateur_id', $match['id'])
                               ->whereIn('match_id', $matchByPhase)
                               ->count();
                //$nbPari = Paris::where('utilisateur_id', $match['id'])->count();
           }
           $matchs['indiv'][$key]['p3'] = $pari3;
           $matchs['indiv'][$key]['p2'] = $pari2;
           $matchs['indiv'][$key]['p1'] = $pari1;
           //$matchs['indiv'][$key]['nbPari'] = $nbPari;
      }

      // Maintenant on tri le tableau par ordre de pari a 3 points gagnés
      /*usort($matchs['indiv'], function($a, $b) {
        if($a['points'] == $b['points']) {
            if($a['p3'] == $b['p3']) {
                if($a['p2'] == $b['p2']) {
                    if($a['p1'] == $a['p1']) {
                        //return $a['nbPari'] < $b['nbPari'];
                        return 0;
                    } else {
                        return $a['p1'] < $b['p1'];
                    }
                } else {
                    return $a['p2'] < $b['p2'];
                }
            } else {
                return $a['p3'] < $b['p3'];
            }
        }
        return $a['points'] < $b['points'];
      });
*/
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

    const SQL_GET_ALL_MATCH_ID_BY_PHASE = <<<EOF
    SELECT id
    FROM match
    WHERE phase_id = ;phase;
EOF;

}
