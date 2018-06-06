<?php
/**
 * Ensemble de fonctions liées à l'identification.
 *
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 *
 *  PATCH_5_09 : Classe Utilisateur propre à IHMR
*/

require_once __DIR__.'/../ressource/php/constantes/ResultatMatch.php';

class UtilisateurService {

    /**
     * MailService : tilisé pour les notifications de création de compte et de modification de mot de passe
     * @var type
     */
    protected $mailService;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->dateService = SinapsApp::make("DateService");
        $this->mailService = SinapsApp::make("MailService");
    }


    /**
     * Cré l'utilisateur en base de donnée
     */

    public function createUser($nom, $prenom, $login, $email, $passwd, $promo) {
        try {
            // On cré l'utilisateur
            $user = new Utilisateur();
            $user->nom = $nom;
            $user->prenom = $prenom;
            $user->login = $login;
            $user->email = $email;
            $user->password = md5($passwd);
            $user->promotion = $promo;
            $user->points = 0;
            $user->isactif = 0;
            $user->isadmin = 0;
            $user->save();
            return TRUE;

        } catch(Exception $exception) {
            throw $exception;
        }
    }


    /**
     * Suppression d'un utilisateur
     *
     * @param type $id
     * @return boolean
     * @throws Exception
     */
    public function supprimerUtilisateur($id) {
        try {

            OrmQuery::beginTransaction();

            $user = Utilisateur::where('id', $id)->first();
            if ($user === NULL ) {
                throw new Exception("SUPRESSION: Impossible d'identifier l'utilisateur d'id $id");
            }

            // Suppression de l'utilisateur s'il exite dans Utilisateur
            $user->delete();

            OrmQuery::commit();

            return TRUE;

        } catch(Exception $exception) {
            OrmQuery::rollback();
            throw $exception;
        }
    }


    /**
     * Activer un utilisateur
     *
     * @param type $id
     * @return boolean
     * @throws Exception
     */
    public function activerUtilisateur($id) {
        try {

            OrmQuery::beginTransaction();

            $user = Utilisateur::where('id', $id)->first();
            if ($user === NULL ) {
                throw new Exception("ACTIVATION: Impossible d'identifier l'utilisateur d'id $id");
            }

            $user->isactif = Utilisateur::ACTIVE_USER_VALUE;
            $user->save();

            OrmQuery::commit();

            return TRUE;

        } catch(Exception $exception) {
            OrmQuery::rollback();
            throw $exception;
        }
    }
}
