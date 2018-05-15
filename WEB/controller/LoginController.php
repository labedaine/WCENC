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
    private $restClientService;
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


    public function verifieUserSurRestitution() {

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
                    "utilisateur"   => $user,
                    "droits"        => $droits,
                    "id"            => $user->id
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

        Cookie::delete("token");

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
