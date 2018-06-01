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
}
