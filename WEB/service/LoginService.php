<?php
/**
 * Ensemble de fonctions liées à l'identification.
 *
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/


class LoginService {

    // Variable liées au curl
    protected $timeoutCurl = 5;
    protected $url = NULL;
    protected $srvApp = NULL;

    public function __construct() {
        $this->dateService       = App::make("DateService");
        $this->fileService       = App::make("FileService");
    }

     /**
     * Retourne l'utilisateur si le couple login/pass est valide, le code erreur sinon
     *
     * @param string $username le nom de l'utilisateur
     * @param string $password le mot de passe utilisateur
     * @return boolean TRUE si le login est ok, "401" si non ok, "402" si pas de droits
     */

    public function login($username, $password) {
        $retour = NULL;

        $retour = $this->getLogin($username, $password);

        return $retour;
    }

    private function getLogin($username, $password) {

        $user = Utilisateur::where("login", $username)->first();
        
        if ( $user && $user->password === md5($password.strtolower($username))) {

            // L'utilisateur n'est pas actif: 402
            if(!$user->isactif) {
                return 402;
            }

            $retour = $this->performLogin($user);

            return $retour;

        } else {
            return 401;
        }
    }

    /**
     * Detruit la session utilisateur en BDD
     *
     * @param Mixed $utilisateur l'utilisateur à déconnecter
     * @return TRUE
     */

    public function logout($utilisateur) {
        Session::where("utilisateur_id", $utilisateur->id)->delete();
        return TRUE;
    }

    /**
     * Récupère les information de l'utilisateur à partir de son "token"
     *
     * @param string $token le token (fournit par le cookie en général)
     * @return Mixed l'utilisateur
     */

    public function getUtilisateurDepuisToken($token) {

        $session = Session::where("token", $token)->first();

        if ($session === NULL) {
            return NULL;
        }

        return $session->utilisateur;
    }

    /**
     * Crée le token et la session
     *
     * @param Mixed $user l'instance de la classe Utilisateur
     * @return Utilisateur user
     */

    protected function performLogin($user) {

        // Generation du token
        $token = md5(uniqid("bhlsde".$user->login, TRUE));

        // Save session info to DB ...
        $session = new Session();
        $session->token = $token;
        $session->date = App::make("TimeService")->now();

        $user->session()->save($session);

        SinapsApp::setUtilisateurCourant($user);
        Cookie::session("token", $token);
        return $user;
    }
}
