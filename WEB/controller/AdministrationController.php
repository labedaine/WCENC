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
            return JsonService::createResponse($listeUsers); //{"success":true,"code":"","payload":[{"id":1,"nom":"admin","prenom":"admin","login":"admin","email":"admin@betfip.fr","password":"admin","isactif":null,"isadmin":1}]}

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



//     /**
//      * Récupère la liste des groupes de l'utilisateur spécifié
//      */
//     public function getListeGroupes() {
//         $this->applyFilter("authentification");
//         $idUtilisateur= Input::get('idUtilisateur');

//         $listeGroupes = $this->utilisateurService->getListeGroupes($idUtilisateur);

//         $retour = $this->jqGridService->createResponseFromModels(
//             $listeGroupes,
//             $_REQUEST,
//             array(),
//             TRUE
//             );
//         return $retour;
//     }

//     /**
//      * Renvoie la liste des groupes de l'utilisateur en lien avec une ou plusieurs applications
//      * On peut également filttrer la liste par rapport à un niveau de profil défini.
//      * @param array $listeApplicationId liste des Ids d'applications
//      * @param type $filtrageNiveau $filtrageNiveau si > 0, on filtre sur un niveau de profil spécifique
//      * @return type
//      */
//     public function getGroupesUtilisateurParApplicationEtNiveau(
//         array $listeApplicationId,
//         $filtrageNiveau = NULL
//         ) {
//             /// DROITS_SERVICE : a dégager
//             $this->applyFilter("authentification");

//             $listeGroupes = $this->droitsService->getGroupesUtilisateurParApplicationEtNiveau(
//                 $listeApplicationId,
//                 $filtrageNiveau
//                 );

//             $result["groupes"] = $listeGroupes;

//             $retour = $this->jsonService->createResponseFromArray($result);
//             return $retour;
//     }

//     public function getDroits() {

//         $this->applyFilter("authentification");
//         /// DROITS_SERVICE : a dégager
//         $result["profils"] = $this->droitsService->getProfilsUtilisateur();

//         $profil = $result["profils"]["monProfil"];
//         $preferences = json_decode($this->getPreferences())->payload->preferences;
//         $typeUtilisateur = "";

//         foreach( $preferences as $preference ) {
//             if($preference->clef === "typeUtilisateur") {
//                 $typeUtilisateur = $preference->valeur;
//             }
//         }

//         $mesModules = SinapsApp::$config['droits.niveau.' . $profil];
//         $mesModules = explode(';', $mesModules);
//         $mesModules = array_filter($mesModules);

//         if ($profil === "0") {
//             if( $typeUtilisateur === 'superviseur' ) {
//                 if(!empty($mesModules)) {
//                     if($mesModules[array_search('admin-PSN:rw', $mesModules)] !== FALSE ) {
//                         unset($mesModules[array_search('admin-PSN:rw', $mesModules)]);
//                     }
//                     if($mesModules[array_search('vueRapports:rw', $mesModules)] !== FALSE ) {
//                         unset($mesModules[array_search('vueRapports:rw', $mesModules)]);
//                     }
//                     if($mesModules[array_search('mes-applications:rw', $mesModules)] !== FALSE ) {
//                         $mesModules[array_search('mes-applications:rw', $mesModules)] = 'mes-applications:r';
//                     }
//                 }
//             }
//         }

//         // Cas EOM (super Utilisateur)
//         if ($profil === "4") {
//             if( $typeUtilisateur !== 'EOM' ) {
//                 if(!empty($mesModules)) {
//                     if($mesModules[array_search('admin-PSN:r', $mesModules)] !== FALSE ) {
//                         unset($mesModules[array_search('admin-PSN:r', $mesModules)]);
//                     }
//                 }
//             }
//         }

//         $result["modules"] = array();


//         foreach ( $mesModules as $module) {
//             $temporaire = explode(':', $module);
//             $result["modules"][] = array( "module" => $temporaire[0], "access" => $temporaire[1]);
//         }

//         $retour = $this->jsonService->createResponseFromArray($result);
//         return $retour;
//     }

//     /**
//      * reçoit les domaines qui lui sont assignés
//      *
//      * @return array $domaines
//      */

//     public function getDomaines() {
//         $this->applyFilter("authentification");

//         // utilisateur courant
//         $user = SinapsApp::utilisateurCourant();

//         // On checke si le user est dans le memcache
//         // S'il n'est pas présent on l'ajoute
//         $mesDomaines = $this->utilisateurService->getMesDomaines($user->id);

//         $retour = $this->jsonService->createResponseFromArray($mesDomaines);
//         return $retour;
//     }

//     /**
//      * Récupère les préférences de l'utilisateur
//      * @return type
//      */
//     public function getPreferences() {

//         $this->applyFilter("authentification");

//         $user = SinapsApp::utilisateurCourant();

//         // Préférences de l'utilisateur
//         $preferences = UtilisateurPreference::where('Utilisateur_id', $user->id)->get();

//         $result["preferences"] = array();
//         foreach ( $preferences as $preference) {
//             $result["preferences"][] = array( "clef" => $preference->clef, "valeur" => $preference->valeur);


//             // Traitement HOMEPAGE : si homepage <> 'BAC_A_ALERTE', on recherche l'id du tableau de bord
//             if ($preference->clef === 'homepage') {
//                 if ($preference->valeur !== 'BAC_A_ALERTE') {
//                     $nomCompletTDB = $preference->valeur;
//                     $tabItems = explode('.', $nomCompletTDB);
//                     $nomApplication = array_shift($tabItems);
//                     $nomTDB = implode('.', $tabItems);
//                     // On recherche le tableau de bord
//                     $objApplication = Application::where('nom', $nomApplication)->first();
//                     if ($objApplication) {
//                         $objTDB = TableauDeBord::where('nom', $nomTDB)
//                         ->where('Application_id', $objApplication->id)
//                         ->first();
//                         if ($objTDB) {
//                             $result["preferences"][] = array( "clef" => 'idTableauDeBord',
//                                 "valeur" => $objTDB->id);
//                             // Infos pour la homepage
//                         } else {
//                             $valeur = 'Aucun TDB pour l\'application '.$nomApplication;
//                             $result["preferences"][] = array( "clef" => 'homepage_infos',
//                                 "valeur" => $valeur);
//                             // Infos pour la homepage
//                         }
//                     } else {
//                         $valeur = 'TDB : impossible d\'identifier l\'application '.$nomApplication;
//                         $result["preferences"][] = array( "clef" => 'homepage_infos',
//                             "valeur" => $valeur); // Infos pour la homepage
//                     }
//                 }
//             }
//         }

//         // On crée, par défaut, le typeUtilisateur = superviseur pour les N0 dont ce n'est pas positionné
//         $monProfil["profils"] = $this->droitsService->getProfilsUtilisateur();

//         if( isset($monProfil["profils"]["monProfil"])) {
//             $monProfil = $monProfil["profils"]["monProfil"];

//             if( $monProfil == 0 ) {
//                 $trouve = FALSE;
//                 if(empty($result['preferences'])) {
//                     $result['preferences'][] = array( "clef" => 'typeUtilisateur',
//                         "valeur" => 'superviseur');
//                 }

//                 foreach($result['preferences'] as  $index => $preference ) {

//                     if( $preference['clef'] === 'typeUtilisateur' ) {
//                         $trouve = TRUE;
//                         if($preference['valeur'] == "" ) {
//                             $result['preferences'][$index]['valeur'] = 'superviseur';
//                         }
//                     }
//                     if( $trouve == FALSE ) {
//                         $result['preferences'][] = array( "clef" => 'typeUtilisateur',
//                             "valeur" => 'superviseur');
//                     }
//                 }
//             }
//         }

//         $retour = $this->jsonService->createResponseFromArray($result);

//         return $retour;
//     }

//     /**
//      * Ajout / modification d'un utilisateur
//      * */

//     public function enregistrerUtilisateur() {
//         try {
//             $this->applyFilter("authentification");

//             $idUtilisateur = (integer) Input::get('id', 0);
//             $login = Input::get('login');
//             $nom = Input::get('nom');
//             $prenom = Input::get('prenom');
//             $email = Input::get('email');
//             $pwd = Input::get('pwd');
//             $gestionHabilitations = Input::get('gestionHabilitations');
//             $gestionEOM = Input::get('gestionEOM');
//             $groupes = Input::get('groupes');

//             // Valeur retournée par la méthode
//             $objReponse=new stdClass();
//             $objUtilisateur = NULL;
//             $envoyerMailCreation = FALSE;
//             $envoyerMailModification = FALSE;
//             $envoyerMailMajPWD = FALSE;
//             $mailEnvoye = FALSE;
//             $objValeursModifiees = NULL;

//             // Appel de la méthode UtilisateurService.enregistrerUtilisateur
//             if ($idUtilisateur > 0) {
//                 // MODIFICATION UTILISATEUR
//                 // On vérifie le nombre de valeur modifiées :
//                 // Si égal à 0, on sort sans enregistrer
//                 // Si > 0, la variable de retour servira au message de notification
//                 $objValeursModifiees = $this->utilisateurService->getInfosUserAvantModification(
//                     $idUtilisateur,
//                     $nom, $prenom,
//                     $email, $pwd,
//                     $groupes,
//                     $gestionHabilitations, $gestionEOM
//                     );
//                 if ($objValeursModifiees->nbModifications === 0) {
//                     throw new Exception ("Aucune modification n'a été effectuée");
//                 }

//                 $objUtilisateur = $this->utilisateurService->modifierUtilisateur(
//                     $idUtilisateur,
//                     $nom, $prenom,
//                     $email, $pwd,
//                     $groupes);
//                 // Envoi Mail : on recherche les éléments modifiés
//                 if ($objUtilisateur) {
//                     $envoyerMailModification = TRUE;
//                 }

//             } else {
//                 $creationUtilisateur = TRUE;
//                 // CREATION UTILISATEUR
//                 $objUtilisateur = $this->utilisateurService->ajouterUtilisateur(
//                     $login,
//                     $nom, $prenom,
//                     $email, $pwd,
//                     $groupes);
//                 if ($objUtilisateur) {
//                     $idUtilisateur = $objUtilisateur->id;
//                     $envoyerMailCreation = TRUE;
//                 }
//             }

//             // Enregistrement de la préférence "Habilitation"
//             $preferenceUtilisateur = UtilisateurPreference::where('Utilisateur_id', $idUtilisateur)
//             ->where('valeur', 'gestionHabilitations')
//             ->get();
//             if ($gestionHabilitations === "1") {
//                 if (count($preferenceUtilisateur) === 0) {
//                     $preferenceUtilisateur = new UtilisateurPreference();
//                     $preferenceUtilisateur->clef = "droitsSpecifiques";
//                     $preferenceUtilisateur->__set("Utilisateur_id", $idUtilisateur);
//                     $preferenceUtilisateur->valeur = "gestionHabilitations";
//                     $preferenceUtilisateur->save();
//                 }
//             } else {
//                 if (count($preferenceUtilisateur) > 0) {
//                     foreach ($preferenceUtilisateur as $item) {
//                         $item->delete();
//                     }
//                 }
//             }
//             // Enregistrement de la préférence "EOM"
//             $preferenceUtilisateur = UtilisateurPreference::where('Utilisateur_id', $idUtilisateur)
//             ->where('valeur', 'EOM')
//             ->get();
//             if ($gestionEOM === "1") {
//                 if (count($preferenceUtilisateur) === 0) {
//                     $preferenceUtilisateur = new UtilisateurPreference();
//                     $preferenceUtilisateur->clef = "typeUtilisateur";
//                     $preferenceUtilisateur->__set("Utilisateur_id", $idUtilisateur);
//                     $preferenceUtilisateur->valeur = "EOM";
//                     $preferenceUtilisateur->save();
//                 }
//             } else {
//                 if (count($preferenceUtilisateur) > 0) {
//                     foreach ($preferenceUtilisateur as $item) {
//                         $item->delete();
//                     }
//                 }
//             }

//         } catch(Exception $err) {
//             $retour = JsonService::createErrorResponse("500", $err->getMessage());
//             return $retour;
//         }
//         // Gestion du Mail (si besoin), et renvoi de la réponse
//         try {
//             // Création de l'objet réponse
//             $objReponse->utilisateur = $objUtilisateur;
//             //            if ($envoyerMailMajPWD ) {
//             //                $this->utilisateurService->notificationNouveauMotDePasse($objUtilisateur);
//             //                $mailEnvoye = TRUE;
//             if ($envoyerMailModification ) {
//                 $this->utilisateurService->notificationModificationUtilisateur($objUtilisateur, $objValeursModifiees);
//                 $mailEnvoye = TRUE;
//             } else if ($envoyerMailMajPWD  || $envoyerMailCreation) {
//                 $this->utilisateurService->notificationCreationUtilisateur($objUtilisateur);
//                 $mailEnvoye = TRUE;
//             }
//             $objReponse->mailEnvoye = $mailEnvoye;
//             $objReponse->mailErrorMsg = '';
//             $retour = $this->jsonService->createResponse($objReponse);
//             return $retour;
//         } catch(Exception $err) {
//             $objReponse->mailEnvoye = FALSE;
//             $objReponse->mailErrorMsg = $err->getMessage();
//             $retour = $this->jsonService->createResponse($objReponse);
//             return $retour;
//         }
//     }


//     public function verifierSaisie() {
//         try {
//             $idUtilisateur = (integer) Input::get('id', 0);
//             $login = Input::get('login');
//             $nom = Input::get('nom');
//             $prenom = Input::get('prenom');
//             $email = Input::get('email');
//             $pwd = Input::get('pwd');
//             $groupes = Input::get('groupes');

//             $retourSrvc = $this->utilisateurService->verifierSaisie(
//                 $idUtilisateur, $login,
//                 $nom, $prenom,
//                 $email, $pwd,
//                 $groupes);

//             $retour = $this->jsonService->createResponse($retourSrvc);
//             return $retour;

//         } catch(Exception $err) {
//             $retour = JsonService::createErrorResponse("500", $err->getMessage());
//             return $retour;
//         }
//     }



//     /**
//      * Renvoie une liste conteant les groupes d'un utilisateur
//      *
//      * @return type renvoie au format json une liste de tableau ("d", "nom")
//      */
//     public function getGroupesDeLUtilisateur() {

//         $this->applyFilter("authentification");
//         $idUtilisateur = Input::get('id', SinapsApp::UtilisateurCourant()->id);

//         $liste = $this->utilisateurService->getNomDesGroupesDuUser($idUtilisateur);

//         $retour = $this->jsonService->createResponseFromArray($liste);
//         return $retour;
//     }


//     // gestion des assignations d'un utilisateur dans des groupes
//     public function gestionAssignationGroupes () {

//         $this->applyFilter("authentification");
//         $idUtilisateur = Input::get('id');
//         $listeAInscrire = Input::get('listeAInscrire');
//         $listeADesinscrire = Input::get('listeADesinscrire');

//         if (count($listeAInscrire) > 0) {
//             $this->inscrireUtilisateurDansGroupe($idUtilisateur, $listeAInscrire);
//         }

//         if (count($listeADesinscrire) > 0) {
//             $this->desinscrireUtilisateurDuGroupe($idUtilisateur, $listeADesinscrire);
//         }

//         return $this->jsonService->createResponse($idUtilisateur);
//     }


//     private function ajoutDesGroupes(&$dataList) {
//         // On le fait sans group concat parce que ne fonctionnant pas et en plus ça permet d'utiliser postgreSQL
//         foreach($dataList as &$rowLine) {
//             $idItem  = $rowLine->id;
//             $tabGroupe = $this->utilisateurService->getNomDesGroupesDuUser($idItem);
//             $rowLine->groupes = join("<br />", $tabGroupe);

//             //            $rowLine->role = $this->utilisateurService->getTypeUtilisateur($idItem);
//         }
//     }

//     /**
//      * Renvoie les informations d'un utilisateur
//      */

//     public function getDetails($matcher) {
//         $userId = $matcher[1];

//         $utilisateur = Utilisateur::where('id',$userId)->first();

//         if ($utilisateur) {
//             // On récupère les préférences
//             $listePrefereces = array();
//             $preferencesUtilisateur = UtilisateurPreference::where('Utilisateur_id', $userId)->get();
//             foreach ($preferencesUtilisateur as $preference) {
//                 $listePrefereces[] = array(
//                     "clef" => $preference->clef,
//                     "valeur" => $preference->valeur
//                 );
//             }

//             $array = array(
//                 "id" => $utilisateur->id,
//                 "nom" => $utilisateur->nom,
//                 "prenom" => $utilisateur->prenom,
//                 "email" => $utilisateur->email,
//                 "login" => $utilisateur->login,
//                 "password" => $utilisateur->password,
//                 "preferences" => $listePrefereces
//             );
//             $retour = $this->jsonService->createResponseFromArray($array);
//         } else {
//             $retour = $this->jsonService->createErrorResponse('601', 'Utilisateur introuvable');
//         }
//         return $retour;
//     }

//     const SQL_LISTE_UTILISATEURS = <<<EOF
//         SELECT
//                 "UTL".id,
//                 "UTL".nom, "UTL".prenom,
//                 "UTL".login, "UTL".email,
//                 '' AS groupes,
//                 CASE WHEN
//                 REPLACE(REPLACE(REPLACE(REPLACE(
//                     (SELECT
//                         string_agg("PREF".valeur, '<br />')
//                         FROM "UtilisateurPreference" AS  "PREF"
//                         WHERE "PREF"."Utilisateur_id" = "UTL".id
//                         AND "PREF".valeur IN ('administrateur', 'superviseur','EOM','gestionHabilitations')
//                         GROUP BY "PREF".valeur
//                         ORDER BY "PREF".valeur
//                     ) ,
//                 'superviseur', 'Superviseur PSN'), 'administrateur', 'Administrateur PSN'),
//                 'EOM', 'EOM Sinaps'), 'gestionHabilitations', 'Gestion des habilitations'
//                 ) IS NOT NULL THEN '' END AS "role"
//         FROM "Utilisateur" AS "UTL"
//         LEFT JOIN "UtilisateurDuGroupe" AS "UTG" ON "UTG"."Utilisateur_id" = "UTL".id
//         LEFT JOIN "Groupe" AS "GRP" ON "UTG"."Groupe_id" = "GRP".id
//         WHERE "UTL"."isActif" = 1
//         GROUP BY "UTL".login, "UTL".id, "GRP".nom
//         ORDER BY "UTL".nom, "GRP".nom
// EOF;

}
