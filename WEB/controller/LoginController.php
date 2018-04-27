<?php
/**
 * Gère le login pour les deux IHM
 *
 * PHP version 5
 *
 * @author Damien André <damien.andre@dgfip.finances.gouv.fr>
 *
 * PATCH_5_09 : Classe Login en charge des authentifications
 */


class LoginController extends BaseController {

    private $TimeService;
    private $jsonService;
    private $restClientService;
    private $loginService;
    private $droitsService;
    private $utilisateurService;
// ATTENTION : DroitsService n'est plus dans COMMUN => utilisé UNIQUEMENT depuis RESTIT

    private $ouSuisJe;

    public function __construct() {

        $this->timeService       = SinapsApp::make("TimeService");
        $this->jsonService       = App::make("JsonService");
        $this->restClientService = App::make("RestClientService");
        $this->loginService      = App::make("LoginService");

        $this->ouSuisJe          = SinapsApp::getConfigValue("ou.suis.je");
    }

    /**
     * Opération de login
    */

    public function postAuth() {

        $retour = NULL;

        if(in_array($this->ouSuisJe, array("configuration", "deploiement_sondes", "formation"))) {
            $retour = $this->postAuthConfiguration();

        } else if($this->ouSuisJe === "restitution") {
            $retour = $this->postAuthRestitution();
        }
        return $retour;
    }

    /*
     * Pour la restitution
     */

    public function postAuthRestitution() {
		
        $this->droitsService = App::make("DroitsService");
        
        App::register("UtilisateurService");
        $this->utilisateurService = App::make("UtilisateurService");

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
            $preferences = UtilisateurPreference::where('Utilisateur_id', $user->id)->get();

            foreach($preferences as $preference ) {
                if( $preference->clef === "typeUtilisateur") {
                    $typeUtilisateur = $preference->valeur;
                }
            }

            $monProfil["profils"] = $this->droitsService->getProfilsUtilisateur();

            if( isset($monProfil["profils"]["monProfil"])) {
                $monProfil = $monProfil["profils"]["monProfil"];

                if( $monProfil == 0 && $typeUtilisateur == "" ) {
                    $typeUtilisateur = "superviseur";
                }
            }

            if($typeUtilisateur !== NULL && SinapsApp::$config["ou.suis.je"] === "restitution") {
                $this->utilisateurService->gereUserPSNMemcache($user->id, $typeUtilisateur);
                if( $typeUtilisateur === 'superviseur' ) {

                    $baseUrl = SinapsApp::getConfigValue("repartition.url");
                    $actionUrl = $baseUrl . "nouvelle";

                    try {
                        $response = $this->restClientService->getURL($actionUrl);
                        $repartition = JsonService::parseResponse($response);

                    } catch(Exception $exception) {
                        $this->logger->addError("Erreur lors de la demmande de nouvelle répartition");
                        $this->logger->addError($result);
                        throw $exception;
                    }
                }
            }
            return $retour;
        }
    }

    /*
     * Pour la configuration
     */

    public function postAuthConfiguration() {

        $username = Input::get("login");
        $password = Input::get("password");

        $user = $this->loginService->login($username, $password);

        if ( ($user === 401) || ($user === 402) || ($user === 403) ) {
            $retour = $this->jsonService->createErrorResponse($user);
            return $retour;

        } else {

            $retour = $this->jsonService->createResponse(
                $user
            );

            $user = SinapsApp::utilisateurCourant();
            return $retour;
        }
    }

    /**
     * Opération de login depuis Configuration - Se déroule sur IHMR et renvoye son résultat à IHMC
    */

    public function verifieUserSurRestitution() {

        $this->droitsService = App::make("DroitsService");
        $username = Input::get("login");
        $password = Input::get("passwd");

        $user = $this->loginService->loginDepuisConf($username, $password);

		
        if ( ($user === 401) || ($user === 402) || ($user === 403) ) {
            $retour = $this->jsonService->createErrorResponse($user);
            return $retour;

        } else {

            try {
				$user->password = "";

                // Il faut également les applications auquel a droit l'utilisateur
                $droits = $this->droitsService->getProfilsUtilisateur($user->id);

                // Si aucun droits on sort
                if($droits === NULL) {
                    $user = 402;
                    $retour = $this->jsonService->createErrorResponse(serialize($user));
                    return $retour;
                }

                $aRetourner = array(
					"utilisateur" 	=> $user,
					"droits"		=> $droits,
					"id"			=> $user->id
                );
                $retour = $this->jsonService->createResponse(serialize($aRetourner));

            } catch(Exception $e) {
                $retour = $this->jsonService->createErrorResponse("500", $e->getMessage());
            }

            return $retour;
        }
    }

    /**
     * Opération de login depuis Formation - Se déroule sur IHMR et renvoye son résultat à IHMF
    */

    public function verifieUserFormationSurRestitution() {

        $this->droitsService = App::make("DroitsService");

        $username = Input::get("login");
        $password = Input::get("passwd");

        $user = $this->loginService->loginDepuisFormation($username, $password);

        if ( ($user === 401) || ($user === 402) || ($user === 403) ) {
            $retour = $this->jsonService->createErrorResponse($user);
            return $retour;

        } else {

            try {
				$user->password = "";

                // Il faut également les applications auquel a droit l'utilisateur
                $droits = $this->droitsService->getProfilsUtilisateur($user->id);

                // Si aucun droits on sort
                if($droits === NULL) {
                    $user = 402;
                    $retour = $this->jsonService->createErrorResponse(serialize($user));
                    return $retour;
                }

                $aRetourner = array(
					"utilisateur" 	=> $user,
					"droits"		=> md5($droits['monProfil']),
					"id"			=> $user->id
                );
                $retour = $this->jsonService->createResponse(serialize($aRetourner));

            } catch(Exception $e) {
                $retour = $this->jsonService->createErrorResponse("500", $e->getMessage());
            }

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

        Cookie::delete(SinapsApp::$config['ou.suis.je'] . "_token");

        $retour = $this->jsonService->createResponseFromArray(array());
        return $retour;
    }

    public function getDetail() {

        $this->applyFilter("authentification");

        $utilisateurCourant = SinapsApp::utilisateurCourant()->toArray();
        unset($utilisateurCourant['password']);

        $retour = $this->jsonService->createResponseFromArray($utilisateurCourant);
        return $retour;
    }

    public function getIdByLogin($matcher) {
        $userLogin = $matcher[1];

        $utilisateur = Utilisateur::where('login', $userLogin)->first();

        if (!$utilisateur) {
            $retour = $this->jsonService->createErrorResponse(401);
            return $retour;

        } 

        $result = new \stdClass();
        $result->id = $utilisateur->id;

        $retour = $this->jsonService->createResponse(
            $result
        );

        return $retour;
    }

    protected function handleException(SinapsException $e) {

        $retour = $this->jsonService->createErrorResponse(401);
        return $retour;
    }
}
