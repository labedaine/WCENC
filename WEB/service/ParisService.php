<?php
/**
 * Ensemble de fonctions liées à l'identification.
 *
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/


class ParisService {

    // Variable liées au curl
    // Variable liées au curl
    protected $timeoutCurl = 5;
    protected $url = NULL;
    protected $srvApp = NULL;

    public function __construct() {
        $this->loginService         = SinapsApp::make("LoginService");
    }


    public function sauvegarderParis($user, $idMatch, $scoreDom, $scoreExt) {

      try {

          if (isset($scoreDom) && isset($scoreExt) && preg_match("/^[\d]+$/", $scoreDom) && preg_match("/^[\d]+$/", $scoreExt))
          {
            //$user = $this->loginService->getUtilisateurDepuisToken(Cookie::get('token'));
            $match = Match::where("id", $idMatch)->first();

            if (strtotime($match->date_match) < strtotime('now'))
            {
              $paris = Paris::where("match_id", $idMatch)->where("utilisateur_id", $user)->first();
              if (!$paris)
                $paris = new Paris();
              $paris->match_id = $idMatch;
              $paris->utilisateur_id = $user;
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
            $match = Match::where("id", $idMatch)->first();
            
            if ($match->score_dom != null && $match->score_ext != null /*&& strtotime($match->date_match) > strtotime('now') */ ) {
                
                $listeParis = Paris::where("match_id", $idMatch)->get();
                foreach ($listeParis as $paris) {
                    if (!$paris) {
                        
                        int $pointsAcquis = 0;
                        int $coef = $match->phase_id;
                        if($coef < 5) {
                            $coef = 1;
                        }
                        
                        if ($pari->score_dom != null && $pari->score_ext != null) {
                            
                            // score exacte
                            if ($pari->score_dom == $match->score_dom && $pari->score_ext == $match->score_ext) {
                                
                                $pointsAcquis += 3*$coef;
                                
                            } else {
                  
                                // vainqueur ou match null trouver
                                if ((($pari->score_dom == $pari->score_ext) && ($match->score_dom == $match->score_ext))
                                 || (($pari->score_dom > $pari->score_ext) && ($match->score_dom > $match->score_ext))
                                 || (($pari->score_dom < $pari->score_ext) && ($match->score_dom < $match->score_ext))) {
                                    
                                     $pointsAcquis += 1*$coef;
                                    
                                    // ecart exacte
                                    if (($pari->score_dom - $pari->score_ext) == ($match->score_dom - $match->score_ext)) {
                                        $pointsAcquis += 1*$coef;
                                    }
                                    
                                } 
                            }
                        }

                        $paris->points_acquis = $pointsAcquis;
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

}
