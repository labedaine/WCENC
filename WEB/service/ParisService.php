<?php
/**
 * Ensemble de fonction utiles pour le calcul des points
 *
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/


class ParisService {

    public function __construct() {
        $this->timeService = SinapsApp::make("TimeService");
    }


    public function sauvegarderParis($user, $idMatch, $scoreDom, $scoreExt) {

      try {

          if (isset($scoreDom) && isset($scoreExt) && preg_match("/^[\d]+$/", $scoreDom) && preg_match("/^[\d]+$/", $scoreExt))
          {
            $match = Match::find($idMatch);

            // Si le match n'est pas passé
            if ($match->date_match > $this->timeService->now()) {

              $paris = Paris::where("match_id", $idMatch)
                            ->where("utilisateur_id", $user)
                            ->first();
              if (!$paris) {
                  $paris = new Paris();
                  $paris->match_id = $idMatch;
                  $paris->utilisateur_id = $user;
              }

              $paris->score_dom = $scoreDom;
              $paris->score_ext = $scoreExt;
              $paris->save();

              return TRUE;
            }
          }
          return FALSE;

      } catch(Exception $exception) {
          throw $exception;
      }


    }



    public function calculerPointsParis($idMatch) {

        try {
            $match = Match::find($idMatch);

            // Si le match est terminé
            if ($match->etat_id == 6) {

                $listeParis = $match->paris;
                foreach ($listeParis as $paris) {
                    if ($paris) {

                        $pointsAcquis = 0;
                        $coef = $match->phase_id;
                        if($coef < 4) {
                            $coef = 1;
                        } else {
                            $coef=$coef-2;
                        }

                        if ($paris->score_dom !== NULL) {

                            // score exacte
                            if ($paris->score_dom == $match->score_dom && $paris->score_ext == $match->score_ext) {

                                $pointsAcquis += 3*$coef;

                            } else {

                                // vainqueur ou match null trouver
                                if ((($paris->score_dom == $paris->score_ext) && ($match->score_dom == $match->score_ext))
                                 || (($paris->score_dom > $paris->score_ext) && ($match->score_dom > $match->score_ext))
                                 || (($paris->score_dom < $paris->score_ext) && ($match->score_dom < $match->score_ext))) {

                                    $pointsAcquis += 1*$coef;

                                    // ecart exacte
                                    if (($paris->score_dom - $paris->score_ext) == ($match->score_dom - $match->score_ext)) {
                                        $pointsAcquis += 1*$coef;
                                    }

                                }
                            }
                        }
                        $paris->points_acquis = $pointsAcquis;
                        var_dump($pointsAcquis);
                        $paris->save();
                    }
                }

                return TRUE;
            }

            return FALSE;

        } catch(Exception $exception) {
            throw $exception;
        }


    }


    public function miseAJourPointsUtilisateurs() {

        $sqlQuery = self::SQL_UPDATE_TOTAL_POINTS_USER;

        $dbh = SinapsApp::make("dbConnection");
        $stmt = $dbh->prepare($sqlQuery);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        return TRUE;
    }


    const SQL_UPDATE_TOTAL_POINTS_USER = <<<EOF
    UPDATE utilisateur u SET points = (
        SELECT COALESCE(SUM(p.points_acquis), 0)
        FROM paris p
        WHERE p.utilisateur_id = u.id
        AND p.points_acquis is not null
    );
EOF;

}
