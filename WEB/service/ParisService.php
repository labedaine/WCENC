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
          if (isset($scoreDom) && isset($scoreExt) && $scoreDom != "")
          {
            //$user = $this->loginService->getUtilisateurDepuisToken(Cookie::get('token'));
            
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
          return FALSE;

      } catch(Exception $exception) {
          throw $exception;
      }


    }

}
