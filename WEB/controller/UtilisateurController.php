<?php
/**
 * Gere l'authentification, les droits, la modification des droits.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 *
 * PATCH_5_09 : Classe Utilisateur propre à IHMR
 */

require_once __DIR__.'/../DTO/UtilisateurDTO.php';

class UtilisateurController extends BaseController {
	
    public function __construct() {
		$this->jsonService = App::make("JsonService");
    }
    
    /**
     * Suppression d'un utilisateur
     * @param type $idUtilisateur
     * @return type
     */
    public function setNotification() {
        try {
            $this->applyFilter("authentification");
            
            $notification = Input::get("notification",0);

            $user = Utilisateur::find(SinapsApp::utilisateurCourant()->id);
			if($user == NULL) {
				throw new Exception("L'utilisateur n'existe pas");
			}
            
            $user->notification = $notification;
            $user->save();
            
            $retour = $this->jsonService->createResponse($user->id);

            return $retour;
        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }

}
