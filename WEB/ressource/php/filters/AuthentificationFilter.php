<?php
/**
 * Verifie la présence d'un cookie valide d'authentification.
 *
 * Set Sinaps::utilisateurCourant si l'authentification a réussi
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class AuthentificationFilter extends Filter {
    /**
     * Service de sécurité.
     *
     * @var LoginService $loginService
     */
    private $loginService;

    /**
     * Contructeur: initialise les services
     */
    public function __construct() {
        $this->loginService = SinapsApp::make("LoginService");
    }

    /**
     * Applique le filter.
     *
     * Lance une SinapsException:
     * 	Si le cookie nommé "token" n'existe pas
     *  Si le login/pass ne matche pas
     *
     *  Sinon set l'utilisateur courant de SinapsApp
     *
     * @see    Filter::apply()
     */
    public function apply() {

        if ( Cookie::has(SinapsApp::$config['ou.suis.je']."_token") === FALSE) {
            $this->throw401();
        }

        $token = Cookie::get(SinapsApp::$config['ou.suis.je']."_token");
        $user = $this->loginService->getUtilisateurDepuisToken($token);

        if ($user === NULL) {
            $this->throw401();
        }

        SinapsApp::setUtilisateurCourant($user);
    }

    /**
     * Renvoie un code "accès denied".
     *
     * @throws SinapsException Envoi une exception de code 401.
     */
    protected function throw401() {
        throw new SinapsException("Access denied", 401);
    }
}
