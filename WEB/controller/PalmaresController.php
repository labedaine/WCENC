<?php

class PalmaresController extends BaseController {

    private $jsonService;


    public function __construct() {
        $this->jsonService = App::make("JsonService");
    }

    /**
     * Récupère la liste des groupes de l'utilisateur spécifié
     */
    public function getListePalmares() {
      
      $retour = array();
      
	  // On récupère toutes les compétitions terminées
	  $competitions = Competition::where('encours', 0)
	                             ->get();
	  
	  foreach($competitions as $competition) {
		  $detail = array();
		  
		  // On récupère le palmares
		  $palmares = Palmares::where("competition", $competition->libelle)
		                      ->orderBy('points', 'DESC')
		                      ->get();
		  
		  foreach($palmares as $palma) {
			$arr = array();
			$user = Utilisateur::find($palma->utilisateur_id);
			$arr['id'] = $user->id;
			$arr['login'] = $user->login;
			$arr['points'] = $palma->points;
			$arr['promo'] = UtilisateurExt::numToString($user->promotion);
			$arr['prenom'] = $user->prenom;
			array_push($detail, $arr);
		  }
		  
		  $retourObj = new StdClass();
		  $retourObj->competition_id = $competition->apiid;
		  $retourObj->detail = $detail;
		  
		  $retour[$competition->libelle] = $retourObj;
	  }
	  
      return JsonService::createResponse($retour);
    }
}
