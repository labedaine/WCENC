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

class AdministrationController extends BaseController {

    private $jsonService;
    private $utilisateurService;
    private $mailService;

    public function __construct() {

        $this->utilisateurService = App::make("UtilisateurService");
        $this->mailService = App::make("MailService");
        $this->jsonService = App::make("JsonService");
    }


    /**
     * retourne la liste des utilisateurs
     * @param type $idUtilisateur
     * @return type
     */
    public function getUtilisateursListe() {
        try {
            $this->applyFilter("administration");

            $mesUsers = Utilisateur::all();
            $listeUsers = array();
            foreach ($mesUsers as $user) {
                $user->promotion = UtilisateurExt::numToString($user->promotion);
                $tmp = $user->toArray();
                unset($tmp->password);
                $listeUsers[] = $tmp;
            }

            return JsonService::createResponse($listeUsers);

        } catch(SinapsException $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }

    /**
     * Suppression d'un utilisateur
     * @param type $idUtilisateur
     * @return type
     */
    public function supprimerUtilisateur() {

        try {
            $this->applyFilter("administration");

            $idUtilisateur = Input::get('userId');
            $this->utilisateurService->supprimerUtilisateur($idUtilisateur);
            $retour = $this->jsonService->createResponse($idUtilisateur);

            return $retour;
        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }

    /**
     * Activer un utilisateur
     * @param type $idUtilisateur
     * @return type
     */
    public function activerUtilisateur() {

        try {
            $this->applyFilter("administration");

            //activation de l'utilisateur en bdd
            $idUtilisateur = Input::get('userId');
            $this->utilisateurService->activerUtilisateur($idUtilisateur);

            $user = Utilisateur::find($idUtilisateur);
            $this->mailService->envoyerMailActivationCompte($user->email, $user->prenom);

            $retour = $this->jsonService->createResponse($idUtilisateur);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }
    
    /**
     * Change le mot de passe
     * @param type $idUtilisateur
     * @return type
     */
    public function renewMdp() {

        try {

            //activation de l'utilisateur en bdd
            $userLogin = Input::get('login');
            $user = Utilisateur::where('login', $userLogin)->first();
            
            if($user === NULL) {
				throw new Exception("Si en plus tu connais pas ton login... '$userLogin' n'existe pas.");
			}
			
			$nouveauMdp = $this->chaine_aleatoire(8);
			
            $this->utilisateurService->changerMdp($user->id, $nouveauMdp);

            $this->mailService->envoyerMailMdp($user->email, $user->prenom, $nouveauMdp);

            $retour = $this->jsonService->createResponse($user->id);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }
    
    public function getListeMails() {
		try {
			$mails = "";
            $users = Utilisateur::all();
            
            foreach($users as $user) {
				$mails .= $user->email . ";";
			}

            $retour = $this->jsonService->createResponse($mails);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
	}
	
	public function ajouterCompetition() {
		try {
			$libelle = Input::get('libelle');
			$apiid = Input::get('apiid');
			
            $competition = Competition::where('libelle', $libelle)->first();
			if($competition) {
				throw new Exception("La competition '".$competition->libelle."' existe déjà.");
			}
			
			$competition = Competition::where('apiid', $apiid)->first();
			if($competition) {
				throw new Exception("La competition d'id '".$competition->apiid."' existe déjà.");
			}
			
			$competition = new Competition();
			$competition->libelle = $libelle;
			$competition->apiid = $apiid;
			$competition->moffset = 0;
			$competition->encours = 0;
			$competition->save();
			
            $retour = $this->jsonService->createResponse($competition->id);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
	}
	
	public function getListeCompetitions() {
		try {
            $competitions = Competition::all();
			$arr = array();

			foreach($competitions as $competition) {
				$aRetourner = new stdClass();
				$aRetourner->id = $competition->id;
				$aRetourner->libelle = $competition->libelle;
				$aRetourner->apiid = $competition->apiid;
				$aRetourner->encours = $competition->encours;
				array_push($arr, $aRetourner);
			}
			
            $retour = $this->jsonService->createResponse($arr);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
	}
    
     /**
     * Change le mot de passe
     * @param type $ancienMdp
     * @param type $nouveauMdp
     * @return type
     */
    public function changeMdp() {

        try {

            //activation de l'utilisateur en bdd
            $userId = Input::get('userId');
            $ancienMdp = Input::get('ancienMdp');
            $nouveauMdp = Input::get('nouveauMdp');

            $user = Utilisateur::find($userId);
            
            if($user === NULL) {
				throw new Exception("Erreur lors de la mise à jour du mot de passe");
			}
			
			if($user->password != md5($ancienMdp)) {
				throw new Exception("L'ancien mot de passe est incorrect.");
			}
			
			if(strlen($nouveauMdp) < 6) {
				throw new Exception("Le nouveau mot de passe doit faire au moins 6 caractères.");
			}
					
            $this->utilisateurService->changerMdp($user->id, $nouveauMdp);

            $retour = $this->jsonService->createResponse($user->id);

            return $retour;

        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }
    
    public function chaine_aleatoire($nb_car, $chaine = 'azertyuiopqsdfghjklmwxcvbn123456789')
	{
		$nb_lettres = strlen($chaine) - 1;
		$generation = '';
		for($i=0; $i < $nb_car; $i++)
		{
			$pos = mt_rand(0, $nb_lettres);
			$car = $chaine[$pos];
			$generation .= $car;
		}
		return $generation;
	}
}
