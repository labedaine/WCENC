<?php
/**
 * Gère le login pour les deux IHM
 *
 * PHP version 5
 *
 * @author Damien André <damien.andre@dgfip.finances.gouv.fr>
 *
 */


class LoginController extends BaseController {

    private $TimeService;
    private $jsonService;
    private $loginService;
    private $utilisateurService;


    public function __construct() {

        $this->timeService          = SinapsApp::make("TimeService");
        $this->jsonService          = App::make("JsonService");
        $this->loginService         = App::make("LoginService");
        $this->utilisateurService   = App::make("UtilisateurService");
    }

    /**
     * Opération de login
    */

    public function postAuth() {

        $username = Input::get("login");
        $password = Input::get("password");

        $user = $this->loginService->login($username, $password);

        if ( ($user === 401) || ($user === 402) ) {
            $retour = $this->jsonService->createErrorResponse($user);
            return $retour;

        } else {

            $retour = $this->jsonService->createResponseFromArray(
                array("nom" => $user->nom, "prenom" => $user->prenom)
            );

            $user = SinapsApp::utilisateurCourant();
            $typeUtilisateur = NULL;
            return $retour;
        }
    }

    /**
     * Opération de logout
     */

    public function deleteAuth() {

        $this->applyFilter("authentification");

        $user = SinapsApp::utilisateurCourant();

        $retourLogout = $this->loginService->logout($user);

        Cookie::delete("token");

        $retour = $this->jsonService->createResponseFromArray(array());
        return $retour;
    }

    public function getDetail() {

        $this->applyFilter("authentification");

        $utilisateurCourant = SinapsApp::utilisateurCourant()->toArray();
        unset($utilisateurCourant['password']);

		$equipes = Equipe::all();
		$utilisateurCourant['equipes'] = array();
		foreach($equipes as $equipe) {
			$objEquipe = new stdClass();
			$objEquipe->id = $equipe->id;
			$objEquipe->pays = $equipe->pays;
			
			array_push($utilisateurCourant['equipes'], $objEquipe);
		}

        // On cherche maintenant la competition en cours
        // Est ce qu'il y a une competition en cours
		// Normalement il n'y en a qu'une ...
		$compet = Competition::where('encours' , 1)->first();

		$utilisateurCourant['competition_id'] = NULL;
		$utilisateurCourant['competition_libelle'] = NULL;
		$utilisateurCourant['competition_encours'] = NULL;
		$utilisateurCourant['competition_hasstart'] = NULL;
		$utilisateurCourant['competition_apiid'] = NULL;
		
		$utilisateurCourant['competition_votrevainqueur'] = NULL;
		
		if($compet !== NULL) {
					  
			$utilisateurCourant['competition_id'] = $compet->id;
			$utilisateurCourant['competition_libelle'] = $compet->libelle;
			$utilisateurCourant['competition_encours'] = $compet->encours;
			$utilisateurCourant['competition_hasstart'] = 0;
			$utilisateurCourant['competition_apiid'] = $compet->apiid;
			
			$pronostic = Pronostic::where('competition_id', $compet->id)
								  ->where('utilisateur_id', $utilisateurCourant['id'])
								  ->first();
			
			if($pronostic != NULL) {
				$utilisateurCourant['pronostic'] = $pronostic->equipe_id;
			}
			
			$match = Match::where('id','>',0)->orderBy('date_match')->first();
			if($match != NULL) {
				$utilisateurCourant['competition_hasstart'] = ($match->date_match > $this->timeService->now() ? 0 : 1);
			}
		}

        $retour = $this->jsonService->createResponseFromArray($utilisateurCourant);
        return $retour;
    }

    public function isLoginInUse($matcher) {
        $userLogin = $matcher[1];
        $utilisateur = Utilisateur::where('login', $userLogin)->first();

        if (!$utilisateur) {
            http_response_code(200);
            return;
        }

        http_response_code(406);
        return;
    }

    public function enregistrerUtilisateur() {

        $login = Input::get("login");
        $email = Input::get("email");
        $prenom = Input::get("prenom");
        $nom = Input::get("nom");
        $pwd = Input::get("pwd");
        $promo = Input::get("promo");

        $utilisateur = Utilisateur::where('login', $login)->first();

        if ($utilisateur) {
            $retour = $this->jsonService->createErrorResponse(406, "L'utilisateur '$login' existe déjà.");
            return $retour;
        }

        try {
            $this->utilisateurService->createUser($nom, $prenom, $login, $email, $pwd, $promo);

        } catch(Exception $e) {
            $retour = $this->jsonService->createErrorResponse($e->getMessage());
        }

        $retour = $this->jsonService->createResponse("");
        return $retour;
    }

    protected function handleException(SinapsException $e) {

        $retour = $this->jsonService->createErrorResponse(401);
        return $retour;
    }
}
