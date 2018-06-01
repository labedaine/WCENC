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
        $this->fileService = SinapsApp::make("FileService");
        $this->mailService = SinapsApp::make("MailService");
        $this->restClientService = App::make("RestClientService");
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
            $user->password = md5($passwd.strtolower($username));
            $user->promotion = $promo;
            $user->isactif = 0;
            $user->isadmin = 0;
            $user->save();
            return TRUE;

        } catch(Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Crée l'utilisateur dans le memcache ou le détruit
     *
     * @param int     $user              id
     * @param boolean $supprDansMemcache si true on supprime l'entrée sinon on l'update/ajoute
     */
    public function gereUserPSNMemcache($userId, $typeUtilisateur, $supprDansMemcache=FALSE) {

        // Si ce n'est pas un superviseur/administrateur, on sort
        if( !in_array($typeUtilisateur, array('superviseur', 'administrateur')) ) {
            return NULL;
        }

        $personnesConnectees = SinapsMemcache::get("superviseursConnectes");
        if( $personnesConnectees === FALSE ) {
            $personnesConnectees = array();
        }
        else {
            $personnesConnectees = $personnesConnectees->valeur;
        }

        if( $supprDansMemcache === FALSE ) {
            $personnesConnectees[$typeUtilisateur][$userId] = App::make("TimeService")->now();
        }
        else {
            unset($personnesConnectees[$typeUtilisateur][$userId]);
        }

        // On sauvegarde l'entrée superviseursConnectes
        SinapsMemcache::set("superviseursConnectes", $personnesConnectees, 0, App::make("TimeService")->now());
    }


    /**
     * Cette méthode renvoie un objet contenant les informations modifiées
     * lors de l'enregistrment d'une fichie Utiliçsateur.
     * Cette méthode est exécutée AVANT l'enregistrement (depuis le controller)
     * L'objet renvoyé par cette méthode permet :
     *    - de savoir si il est nécessaire de procéder à l'enregitrement
     *    - le cas échéant de fournir les informations pour la notification mail de modification
     * @param type $id ID de l'utilisateur concerné (doit être > 0)
     * @param type $nom Nom (valeur modifiée)
     * @param type $prenom Prénom (valeur modifiée)
     * @param type $email Email (valeur modifiée)
     * @param type $pwd   Mot de passe (valeur modifiée)
     * @param array $groupes Liste des groupes (valeur modifiée)
     * @param type $gestionHabilitations Préférence "Gestion habilitations" (valeur modifiée)
     * @param type $gestionEOM Préférence "EOM Sinaps" (valeur modifiée)
     * @return \stdClass Renvoie un objet de structure suivante :
     * object(stdClass) (6) {
                ["nbModifications"]=> (int) <NB MODIFICATIONS>
                ["modifChamps"]=>
                array(1) {
                  ["nom_du_champ"]=>'nouvelle valeur'
                }
                ["modifPassword"]=> (string) NULL ou 'nouvelle_valeur'
                ["modifGroupesAjoutes"]=>
                array(0) {
     *              <tableau_d'ids_de groupes>
                }
                ["modifGroupesSupprimes"]=>
                array(0) {
     *              <tableau_d'ids_de groupes>
                }
                ["modifPreferences"]=>
                array(0) {
*                  <tableau habilitation : nouvelle_valeur>
                     }
              }
     * @throws Exception
     */
    public function getInfosUserAvantModification($id,
                                                $nom, $prenom,
                                                $email,
                                                $pwd,
                                                array $groupes,
                                                $gestionHabilitations, $gestionEOM
            ) {

        // On récupère les informations servant à notifier les éléments modifiés / supprimés
        $objModifications = new stdClass();
        # =========> si égal à 0, cela implique qu'aucune modification n'a été réellement apportée
        $objModifications->nbModifications = 0; // Nombre de modifications apportées à l'utiliateur
        $objModifications->modifChamps = array(); // Nom, Prénom, Email
        $objModifications->modifPassword = NULL; // Si <> NULL, mot de passe a changé
        $objModifications->modifGroupesAjoutes = array(); // Liste des ids de groueps ajoutes
        $objModifications->modifGroupesSupprimes = array(); // Liste des ids de Groupes supprimés
        $objModifications->modifPreferences= array(); // Liste des des préférences

        $objUtilisateurAvantModif= Utilisateur::where('id', $id)->first();
        if ($objUtilisateurAvantModif === NULL) {
            throw new Exception ("ERREUR: impossible d'identifier l'utilisateur '$id'");
        }
        // Liste des groupes _d

        // On vérifie chaque champ pour vérifier si il a été modifié
        if ($nom !== $objUtilisateurAvantModif->nom) {
            $objModifications->nbModifications++;
            $objModifications->modifChamps['Nom'] = $nom;
        }
        if ($prenom !== $objUtilisateurAvantModif->prenom) {
            $objModifications->nbModifications++;
            $objModifications->modifChamps['Prénom'] = $prenom;
        }
        if ($email !== $objUtilisateurAvantModif->email) {
            $objModifications->nbModifications++;
            $objModifications->modifChamps['Email'] = $email;
        }
        // Mot de  passe
        if ($pwd !== $objUtilisateurAvantModif->password) {
            $objModifications->nbModifications++;
            $objModifications->modifPassword=$pwd;
        }
        // Liste des groupes
        // On compare les deux listes de groupes
        // => on valorise la liste des nouveaux groupes
        // => on valorise la liste des groupes supprimés
        $listeUDGAvantModif= UtilisateurDuGroupe::where('Utilisateur_id', $id)->get();
        $listeGroupesIdentiques = array();
        foreach ($listeUDGAvantModif as $UDGAvantModif) {
            $idGroupe = $UDGAvantModif->Groupe_id;
            if (! in_array($idGroupe, $groupes)) {
                $objModifications->modifGroupesSupprimes[] = $idGroupe;
                $objModifications->nbModifications++;
            } else {
                // on le supprime de la liste des groupes
                $listeGroupesIdentiques[] = $idGroupe;
            }
        }
        // A la fin on fait un array_diff pour avoir la liste des groupes ajoutés
        $tabDiff = array_diff($groupes, $listeGroupesIdentiques);
        $nbGroupesAjoutes = count($tabDiff);
        if ($nbGroupesAjoutes > 0) {
            $objModifications->nbModifications += $nbGroupesAjoutes;
            $objModifications->modifGroupesAjoutes = $tabDiff; // ! $tabDiff est un tableau
        }
        // TRAITEMENT DES PREFERENCES
        // 1; Gestion Habilitation
        $countPrefHab = UtilisateurPreference::where('Utilisateur_id', $id)
                                            ->where('valeur', 'gestionHabilitations')
                                        ->count();
        if ($countPrefHab != $gestionHabilitations) {
            $objModifications->nbModifications++;
            $objModifications->modifPreferences['gestionHabilitations'] = $gestionHabilitations;
        }
        // EOM
        $countPrefEOM = UtilisateurPreference::where('Utilisateur_id', $id)
                                            ->where('valeur', 'EOM')
                                        ->count();
        if ($countPrefEOM != $gestionEOM) {
            $objModifications->nbModifications++;
            $objModifications->modifPreferences['EOM'] = $gestionEOM;
        }

        return $objModifications;
    }



    /**
     * Vérification de l'intégrité des données avant enregistrement (ajout ou modification)
     * Si $id = 0 => contexte ajout :
     *      - nom, prénom,
     * Si $id > 0 => contexte modification :
     *      - login non modifiable
     *
     *
     * @param type              $id si > 0, alors modification
     * @param type $login
     * @param type $nom
     * @param type $prenom
     * @param type $email
     * @param type $pwd
     * @param array $groupes
     * @throws Exception
     */
    public function verifierSaisie( $id, $login,
                                                $nom, $prenom,
                                                $email,
                                                $pwd,
                                                array $groupes
            ) {
        try {

            // Vérification de validité des données fournies
            if ( trim($email) === '' ) {
                throw new Exception ("Champ email obligatoire");
            }
            // email : format valide
            if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception ("Format de l'adresse email invalide");
            }
            // Champ "login" que pour l'ajout (non traité en modification)
            if ((int) $id === 0  && trim($login) === '') {
                throw new Exception ("Champ login obligatoire");
            }
            if ( trim($nom) === '' ) {
                throw new Exception ("Champ nom obligatoire");
            }
            if ( trim($prenom) === '' ) {
                throw new Exception ("Champ prénom obligatoire");
            }
            if ( count($groupes) === 0) {
                throw new Exception ("Au moins un groupe doit être associé à l'utilisateur");
            }

            // TRAITEMENTS EN FONCTION DU CONTEXTE :
            if ($id > 0) {
                // ******  MODIFICATION ******

            } else {
                // ****** AJOUT ******
                // - Unicité du LOGIN
                $count = Utilisateur::where('login', $login)->count();
                if ($count > 0) {
                    throw new Exception ("Le champ login existe déjà");
                }
            }

        } catch(Exception $exception) {
            throw $exception;
        }
    }


    /**
     * Création d'un utilisateur
     *
     * @param type $login login : non vide, unique
     * @param type $nom
     * @param type $prenom
     * @param type $email
     *
     * @return type Retourne l'instance Utilisateur créée dde l'utilisateur créé
     * @throws Exceptioneur
     */
    public function ajouterUtilisateur($login,
                                       $nom, $prenom,
                                       $email, $pwd,
                                       array $groupes) {
        try {

            OrmQuery::beginTransaction();

            $this->verifierSaisie(0, $login, $nom, $prenom, $email, $pwd, $groupes);

            // Vérification si pas doublon NOM + PRENOM + EMAIL
            if ( $this->verifierDoublonLogin($login) !== FALSE ) {
                throw new Exception ("Le login $login existe déjà");
            }
            // Vérification si pas doublon LOGIN
            if ( $this->verifierDoublonNomPrenomCourriel(0, $nom, $prenom, $email) !== FALSE ) {
                throw new Exception ("L'utilisateur $nom $prenom existe déjà");
            }

            // Vérification si pas doublon  EMAIL
            if ( $this->verifierDoublonCourriel($id, $email) !== FALSE ) {
                throw new Exception ("L'adresse de courriel utilisateur $email est déjà utilisé.");
            }

            $user = new Utilisateur();

            $user->nom = $nom;
            $user->prenom = $prenom;
            $user->email = $email;
            $user->login = $login;
            $user->password = $pwd;
            $user->save();

            // Assignation des groupes
            $id = $user->id;
            $this->assignerGroupesALUtilisateur($id, $groupes);

            // Envoi du mail à l"utilisateur


           OrmQuery::commit();

            // Retourne l'objet utilisaleur
           $userRetour = Utilisateur::where('login', $login)->first();
           return $userRetour;

        } catch(Exception $exception) {
            OrmQuery::rollback();
            throw $exception;
        }
    }


    /**
     *
     *
     * @param type $id
     * @param type $nom
     * @param type $prenom
     * @param type $email
     * @return type
     * @throws Exception
     */

    public function modifierUtilisateur($id,
                                $nom, $prenom,
                                $email, $pwd,
                                array $groupes) {
        try {

            OrmQuery::beginTransaction();

            $this->verifierSaisie($id, '', $nom, $prenom, $email, $pwd, $groupes);

            // Vérification si pas doublon NOM + PRENOM + EMAIL
            if ( $this->verifierDoublonNomPrenomCourriel($id, $nom, $prenom, $email) !== FALSE ) {
                throw new Exception ("L'utilisateur $nom $prenom existe déjà");
            }

            // Vérification si pas doublon  EMAIL
            if ( $this->verifierDoublonCourriel($id, $email) !== FALSE ) {
                throw new Exception ("L'adresse de courriel utilisateur $email est déjà utilisé.");
            }

            $user = Utilisateur::where('id', $id)->first();

            if ($user === NULL) {
                throw new Exception("MODIFICATION: Impossible d'identifier l'utilisateur d'id $id.");
            } else {
                $user->nom = $nom;
                $user->prenom = $prenom;
                $user->email = $email;
                $user->password = $pwd;
                $user->save();

                // Assignation des groupes
                $this->assignerGroupesALUtilisateur($id, $groupes);

                OrmQuery::commit();

                // Renvoie l'utilisateur modifié
                $userRetour = Utilisateur::where('id', $id)->first();
                 return $userRetour;
            }

        } catch(Exception $exception) {
            OrmQuery::rollback();
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
                throw new Exception("SUPRESSION: Impossible d'identifier l'utilisateur d'id $id");
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

    /**
     * fonction qui retourne les domaines définis pour un user
     *
     * @param int $userId l'id utilisateur
     * @return array $mesDomaines
     */
    public function getMesDomaines($userId) {
        $mesApplications = array();
        $derniereRepartition = 0;

        $repartition = SinapsMemcache::get('repartitionDomaines');
        $modeDeRepartition = SinapsMemcache::get('superviseursConnectes');

        if( $repartition !== FALSE ) {
            $derniereRepartition = $repartition->create;
            $repartition = $repartition->valeur;
            if( isset($repartition[$userId]) ) {
                foreach( $repartition[$userId] as $dom => $app ) {
                    $mesApplications = array_merge($mesApplications, $app);
                }
            }
        }

        if( $modeDeRepartition !== FALSE ) {
            $modeDeRepartition = $modeDeRepartition->valeur;

            if( isset($modeDeRepartition['modeDeRepartition']) ) {
                $modeDeRepartition = $modeDeRepartition['modeDeRepartition'];
            }
            else
            {
                $modeDeRepartition = "Domaine";
            }
        }
        else {
            $modeDeRepartition = "Domaine";
        }

        $retour['repartition'] = $mesApplications;
        $retour['mode'] = $modeDeRepartition;
        $retour['derniereRepartition'] = $derniereRepartition;
        return $retour;
    }

    /**
     * Assignation d'un ou plusieurs groupes jà un utilisateur
     * Cette méthode privée est utilisée dans lun contexte de transaction
     * depuis les méthodes ajouterUtilisateur() ou modifierUtilisateur()
     *
     * @param type $idUser
     * @param type $liste Tableau d'id de groupes
     * @throws Exception
     */
    private function assignerGroupesALUtilisateur($idUser, array $listeIdGroupes, $creerGroupe=false ) {
        try {
            // On supprime les groupes de l'utilisateur
            UtilisateurDuGroupe::where('Utilisateur_id', $idUser)
                 ->delete();

            // On supprime les profils de l'utilisateur
            ProfilDeLUtilisateur::where('Utilisateur_id', $idUser)
                                ->delete();

            foreach ($listeIdGroupes as $idGroupe) {
                // ID : on vérifie son existence
                $objGroupe = Groupe::where('id', $idGroupe)->first();
                if (! $objGroupe) {
                    throw new Exception ("Le groupe d'id $idGroupe n'existe pas");
                }

                $newGDU = new UtilisateurDuGroupe();
                $newGDU->Utilisateur_id = $idUser;
                $newGDU->Groupe_id = $objGroupe->id;
                $newGDU->save();

                // on fait les profils de l'utilisateur grâce à la table ApplicationDuGroupe
                $mesAppDuGroupe = ApplicationDuGroupe::where('Groupe_id', $idGroupe)->get();

                foreach($mesAppDuGroupe as $appDuGroupe) {
                    // On recherche si la ligne n'existe pas déjà
                    $newPDU = ProfilDeLUtilisateur::where('Utilisateur_id', $idUser)
                                                  ->where('Application_id', $appDuGroupe->Application_id)
                                                  ->first();
                    if($newPDU) {
                        if($newPDU->Profil_id > $appDuGroupe->Profil_id) {
                            continue;
                        }
                    } else {
                        $newPDU = new ProfilDeLUtilisateur();
                    }

                    $newPDU->Utilisateur_id = $idUser;
                    $newPDU->Profil_id = $appDuGroupe->Profil_id;
                    $appExiste = Application::find($appDuGroupe->Application_id);
                    if(!$appExiste) {
                        continue;
                    }
                    $newPDU->Application_id = $appDuGroupe->Application_id;
                    $newPDU->save();
                }
            }

            return TRUE;

        } catch(Exception $exception) {
            throw $exception;
        }
    }


    public function getNomDesGroupesDuUser($id){

        $gdu = UtilisateurDuGroupe::where("Utilisateur_id", $id)->get();
        $tabGroupes = array();
        foreach($gdu as $udg) {
           $tabGroupes[] = $udg->groupe->nom;
        }
        sort($tabGroupes);
        return $tabGroupes;
    }


    /**
     * fonction permettant de rechercher si un login existe déjà en base
     *
     * @param type $login
     * @return boolean
     */
    public function verifierDoublonLogin($login) {

        $count = Utilisateur::where('login', $login)->count();
        if ( $count > 0 ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Vérifie s'il existe déjà un utilisateur avec les
     * mêmes NOM + PRENOM + EMAIL
     *
     * @param type $idUser Id du user (0 si ajout)
     * => pour s'assurer en cas de modification, que l'on exclut de la recherche l'utilisateur courant
     * @param type $nom
     * @param type $prenom
     * @param type $email
     */
    public function verifierDoublonNomPrenomCourriel($idUser, $nom, $prenom, $email) {
        $count = Utilisateur::where('nom', $nom)
                                ->where('prenom', $prenom)
                                ->where('email', $email)
                                ->where('id', '!=', $idUser)
                                ->count();
        if ( $count > 0 ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Vérifie s'il existe déjà un utilisateur avec les
     * mêmes  EMAIL
     *
     * @param type $idUser Id du user (0 si ajout)
     * => pour s'assurer en cas de modification, que l'on exclut de la recherche l'utilisateur courant
     * @param type $email
     */
    public function verifierDoublonCourriel($idUser, $email) {
        $count = Utilisateur::where('email', $email)
                            ->where('id', '!=', $idUser)
                            ->count();
        if ( $count > 0 ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * fonction listant les groupes dont l'utilisateur est membre
     *
     * @param type $idUser
     * @param type $gpesDuUser
     * @return type renvoie un tableau d'objets "Groupe"
     */
    public function getListeGroupes($idUser) {
        $listeRetour = array();
        $gpesDuUser = UtilisateurDuGroupe::where("Utilisateur_id", $idUser)->get();
        foreach ($gpesDuUser as $gdu) {
            $groupe = Groupe::where('id', $gdu->Groupe_id)->first();
            if ($groupe) {
                $listeRetour[] = $groupe;
            }
        }
        return $listeRetour;
    }



    /**
     * FONCTIONS GESTION DES HABILTATIONS
     *
     * GESTION DES UTILISATEURS
     *
     */

    /**
     * fonction pour déterminer le type d'utilisateur
     *
     * @param type $idUser
     * @return int
     */
    public function getTypeUtilisateur($idUser) {

        $listePrefUser = UtilisateurPreference::where("Utilisateur_id", $idUser)-> get();
        if ( isset($listePrefUser[0]) ) {
            foreach ($listePrefUser as $pref) {
                if ( ($pref->clef === "typeUtilisateur") && ($pref->valeur === "administrateur") ) {
                    return 1;
                }
                if ( ($pref->clef === "typeUtilisateur") && ($pref->valeur === "EOM") ) {
                    return 2;
                }
            }
        }
        return 0;
    }


    /**
     * fonction pour déterminer l'ecran d'accueil de l'utilisateur
     *
     * @param type $idUser
     * @return string
     */
    public function getPreferenceEcran($idUser) {

        $listePrefUser = UtilisateurPreference::where("Utilisateur_id", $idUser)-> get();
        $clefOk = FALSE;
        if ( isset($listePrefUser[0]) ) {
            $countListePrefUser = count($listePrefUser);
            for ($i=0; $i < $countListePrefUser; $i++) {
                if ($listePrefUser[$i]->clef === "homepage") {
                    $clefOk = TRUE;
                    if ($listePrefUser[$i]->valeur !== "BAC_A_ALERTE") {
                        return strtr($listePrefUser[$i]->valeur, '_', ' ');
                    } else {
                        return "BAC A ALERTES";
                    }
                    break;
                }
            }
        }
        if (!$clefOk) {
            return "BAC A ALERTES";
        }
    }



    /**
     * fonction pour déterminer les accès depuis Z3
     * l'accès depuis zone3 est enregistrée dans la table/class UtilisateurPreference.
     *
     * @param integer $idUser
     */
    public function getPreferenceAcces($idUser) {

        $listePrefUser = UtilisateurPreference::where("Utilisateur_id", $idUser)-> get();

        if ( isset($listePrefUser[0]) ) {
            foreach ($listePrefUser as $pref) {
                if ( ($pref->clef === "acces") && ($pref->valeur === "zone3") ) {
                    return 1;
                }
            }
        }
        return 0;
    }


     /**
     * fonction de mise à jour du mot de passe dans les Reverse proxy
     * Execution successive des commandes suivantes
     * - "ssh admspoit@spvpbrp1 'htpasswd -b /var/sinaps/validUsers.db ".$login." ".$pass."'";
     * - "ssh admspoit@spvpbrp1 'scp /var/sinaps/validUsers.db admspoit@spvpbrp2:/var/sinaps/validUsers.db'";
     */

    protected function majPasswdSurRP (Utilisateur $utilisateur) {

        $dbh = SinapsApp::make("dbConnection");

        $this->logger->debuterEtape("majPasswdSurRP", "Mise à jour des fichiers de droits d'accès Zone 3");

        $url = SinapsApp::getConfigValue("reverseProxy.majSurRP.URL");
        $fichier = SinapsApp::getConfigValue("reverseProxy.fichier");
        $collecteurs = SinapsApp::getConfigValue("reverseProxy.pivotHostname");
        $rp = SinapsApp::getConfigValue("reverseProxy.hostname");
        $user = SinapsApp::getConfigValue("reverseProxy.user");
        $userKey = SinapsApp::getConfigValue("reverseProxy.userKey");

        $collecteurZ3 = InfrastructureDeSupervision::whereIn('nom', $collecteurs)
                        ->wherein('status', array(EtatMachineConst::STATUS_NOMINAL, EtatMachineConst::STATUS_DEMARRE))
                        ->orderBy('status')
                        ->first();

        if ( isset($collecteurZ3) ) {

            $this->logger->addInfo("Collecteur " . $collecteurZ3->nom . " disponible");

            // construction des commandes
            $cmdMajFichierSurRP1 = vsprintf(
                "htpasswd -b %s %s %s",
                array(
                    $fichier,
                    $utilisateur->login,
                    $utilisateur->password
                )
            );

            $copieFichierSurRP2 = vsprintf(
                "scp %s sinaps@%s:%s",
                array(
                    $fichier,
                    $rp[1],
                    $fichier
                )
            );

            // étapes de connexion aux serveurs proxy
            try {
                $etape = "mise a jour du fichier sur " . $rp[0];

                $this->logger->addDebug("Envoi de la requête http://" . $collecteurZ3->ipv4 . $url);
                $result = $this->restClientService->getURL(
                    "http://" . $collecteurZ3->ipv4 . $url,
                    array(
                        "cmd" => $cmdMajFichierSurRP1,
                        "serveur" => $rp[0],
                        "user" => $user,
                        "userKey" => $userKey
                    )
                );

                $this->restClientService->throwExceptionOnError($result);
                $this->logger->addInfo("Mise à jour du fichier sur " . $rp[0] . " réussie");

                $etape = "copie du fichier sur " . $rp[1];
                $this->logger->addDebug("Envoi de la requête http://" . $collecteurZ3->ipv4 . $url);
                $result = $this->restClientService->getURL(
                    "http://" . $collecteurZ3->ipv4 . $url,
                    array(
                        "cmd" => $copieFichierSurRP2,
                        "serveur" => $rp[0],
                        "user" => $user,
                        "userKey" => $userKey
                    )
                );

                $this->restClientService->throwExceptionOnError($result);

                $this->logger->addInfo("Copie du fichier sur " . $rp[1] . " réussie");
                $this->logger->finirEtape("Mise à jour réussie", "majPasswdSurRP");

            } catch (SinapsException $e) {
                $messageError = "Problème lors de la ".$etape.": ".$e->getMessage();
                throw new Exception($messageError);
            }
        } else {
            throw new Exception("Aucun des collecteurs pivots n'est accessible. (". join(', ', $collecteurs) .")");
        }
    }


    /**
     * Envoi un mail lors de la réinitialisation du mot de passe utilisateur
     *
     * @param Utilisateur $utilisateur
     */

    public function notificationNouveauMotDePasse(Utilisateur $utilisateur) {

        $sujet = SinapsApp::getConfigValue("email.notification.maj.motdepasse.sujet");
        $message = "Bonjour,\n\n";

        // Destinataire : par défaut luser de test sdi il est configuré, sinon le mail de l'utilisateur
        $toUser = SinapsApp::getConfigValue("email.notification.test", $utilisateur->email);

        $message .= "Après réinitialisation, votre nouveau mot de passe est:\t ".$utilisateur->password."\r\n";
        $message .= "Pour rappel, votre identifiant de connexion est:\t ".$utilisateur->login."\r\n";

        if($toUser !== $utilisateur->email) {
            $message.= "\nTEST: Ce mail est normalement envoyé à '". $utilisateur->email . "'.\n";
        }

        $message .= SinapsApp::getConfigValue("email.notification.signature");

        try {

            $retour = $this->mailService->envoyerMail( $toUser, $sujet, $message);
            return $retour;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Envoi un mail lors de la création ou la modification d'un utilisateur
     *
     * @param Utilisateur $utilisateur
     * @param bollean $isCreation            sinon modification
     * @return type
     * @throws Exception
     */
    public function notificationCreationUtilisateur(Utilisateur $utilisateur) {

        $sujet = SinapsApp::getConfigValue("email.notification.creation.compte.sujet");
        $message = "Bonjour,\n\n";

        // Destinataire : par défaut luser de test sdi il est configuré, sinon le mail de l'utilisateur
        $toUser = SinapsApp::getConfigValue("email.notification.test", $utilisateur->email);

        $message .= "Votre compte utilisateur SINAPS v5 a été créé.";
        $message .= "\n\nVos informations de compte sont:\r\n";
        $message .= "- identifiant de connexion: ".$utilisateur->login."\r\n";
        $message .= "- mot de passe: ".$utilisateur->password."\r\n";
        $message .= "- nom: ".$utilisateur->nom."\r\n";
        $message .= "- prénom: ".$utilisateur->prenom."\r\n";

        // Ajout des groupes utilisateurs
        $listeGroupe = UtilisateurDuGroupe::with('Groupe')
                                          ->where('Utilisateur_id', $utilisateur->id)
                                          ->get();
        if ($listeGroupe !== NULL ) {
            $message.= "\nLes groupes auquels vous appartenez sont:\n";
            $strGroupes='';
            foreach ($listeGroupe as $groupe) {
                $strGroupes .= " - " . $groupe->groupe->getLigneInfo() . "\n";
            }
            $strGroupes .= "\n";
            $message.= $strGroupes;
        }

        if($toUser !== $utilisateur->email) {
            $message.= "\nTEST: Ce mail est normalement envoyé à '". $utilisateur->email . "'.\n";
        }

        $message .= SinapsApp::getConfigValue("email.notification.signature");

        try {
            $retour = $this->mailService->envoyerMail( $toUser, $sujet, $message);
            return $retour;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Envoi un mail lors de la création ou la modification d'un utilisateur
     *
     * @param Utilisateur $utilisateur Objet utilisateur APRES enregistrement
     * @param type $objValeursModifiees objet contenant les informations modifiées
     * cet objet est renvoyé par la méthode "getInfosUserAvantModification()"
     * @return type
     * @throws Exception
     */
    public function notificationModificationUtilisateur(Utilisateur $utilisateur, $objValeursModifiees = NULL) {

        // CORRECTIF : Sujet mail erroné (creation.compte.sujet remplacé par modification.compte.sujet)
        $sujet = SinapsApp::getConfigValue("email.notification.modification.compte.sujet");
        $message = "Bonjour,\n\n";

        // Destinataire : par défaut luser de test sdi il est configuré, sinon le mail de l'utilisateur
        $toUser = SinapsApp::getConfigValue("email.notification.test", $utilisateur->email);

        $message .= "Votre compte utilisateur SINAPS v5 (".$utilisateur->login.") a été modifié.\n";

        // Nombre de modifications
        $nbModifications = $objValeursModifiees->nbModifications;
        // CHAMPS MODIFIES :
        // Ce sont les champs "texte" (nom, prénom, email)
        // SAUF le champ PWD (traité juste en dessous)
        $listeDesChampsModifies = $nbModifications = $objValeursModifiees->modifChamps;
        $nbChampsModifies = count($listeDesChampsModifies);
        if ($nbChampsModifies > 0) {
            if ($nbChampsModifies === 1) {
                $message .= "\nL'information de compte suivante a été modifiée :\r\n";
            } else {
                $message .= "\nVos informations de compte suivantes ont été modifiées :\r\n";
            }
            foreach ($listeDesChampsModifies as $champ => $nouvellevaleur) {
                $message .= vsprintf(" - %-15s : %-s\r\n", array($champ, $nouvellevaleur));
            }
        }

        // MOT DE PASSE MODIFIE :
        $modifPwd = $objValeursModifiees->modifPassword;
        if ($modifPwd !== NULL) {
            $message .= "\nVotre mot de passe a été modifié :\r\n";
            $message .= "Nouveau mot de passe : ".$modifPwd."\r\n";
        }

        // GROUPES AJOUTES
        $listeGroupesAjoutes = $objValeursModifiees->modifGroupesAjoutes;
        $nbGroupesAjoutes = count($listeGroupesAjoutes);
        if ($nbGroupesAjoutes > 0) {
            if ($nbGroupesAjoutes === 1) {
                $message .= "\nLe groupe suivant a été ajouté :\r\n";
            } else {
                $message .= "\nLes groupes suivants ont été ajoutés :\r\n";
            }
            foreach($listeGroupesAjoutes as $idGroupe) {
                $groupe = Groupe::where('id', $idGroupe)
                                       ->first();
                if ($groupe) {
                    $message .= " - " . $groupe->getLigneInfo() . "\n";
                }
            }
        }

        // GROUPES SUPPRIMES
        $listeGroupesSupprimes = $objValeursModifiees->modifGroupesSupprimes;
        $nbGroupesSupprimes = count($listeGroupesSupprimes);
        if ($nbGroupesSupprimes > 0) {
            if ($nbGroupesSupprimes === 1) {
                $message .= "\nLe groupe suivant a été supprimé :\r\n";
            } else {
                $message .= "\nLes groupes suivants ont été supprimés :\r\n";
            }
            foreach($listeGroupesSupprimes as $idGroupe) {
                $groupe = Groupe::where('id', $idGroupe)
                                       ->first();
                if ($groupe) {
                    $message .= " - " . $groupe->nom . "\n";
                }
            }
        }

        // HABILITATIONS
        $listePrefsModifiees = $objValeursModifiees->modifPreferences;
        $nbPrefsModifiees = count($listePrefsModifiees);
        if ($nbPrefsModifiees > 0) {
            if ($nbPrefsModifiees ===1) {
                $message .= "\nL'habilitation suivante a été modifiée :\r\n";
            } else {
                $message .= "\nLes habilitations suivantes ont été modifiées :\r\n";
            }
            foreach($listePrefsModifiees as $habilitation => $nouvelleValeur) {
                switch ($habilitation) {
                    case 'gestionHabilitations':
                        $strHabilitation='gestion des habilitations';
                        break;
                    case 'EOM':
                        $strHabilitation='EOM Sinaps';
                        break;
                    default:
                        // Par défaut (on sait jamais), on met la valeur présente dans le tableau.
                        // Si c'est le cas, il faut traiter un libellé "propre" dans un "case" dédié
                        $strHabilitation = $habilitation;
                        break;
                }
                // Ajouté ou supprimé?
                $action = ($nouvelleValeur === '0' ? 'supprimée' : 'ajoutée');
                $message .= vsprintf(
                                            " - L'habilitation \"%s\" a été %s.\n",
                                                array($strHabilitation, $action)
                                        );
            }
        }

        // Finalisation du message
        if($toUser !== $utilisateur->email) {
            $message.= "\nTEST: Ce mail est normalement envoyé à '". $utilisateur->email . "'.\n";
        }

        $message .= SinapsApp::getConfigValue("email.notification.signature");

        try {
            $retour = $this->mailService->envoyerMail( $toUser, $sujet, $message);
            return $retour;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Envoi un mail lors de la modification d'une application
     *
     * @param int $idApplication
     * @param array $merge        contient les infos utiles pour envoie de mail (profil_id, groupe_id, booleen ajout)
     * @return type
     * @throws Exception
     */

    public function notificationModificationApplication($idApplication, $merge) {

        try {
            $retour = false;

            $sujet = SinapsApp::getConfigValue("email.notification.modification.habilitation.sujet");

            $msgGrp = "\nInformations sur le groupe:\n %s.\n";
            $msgAdd = "Le groupe '%s' auquel vous êtes rattaché est désormais habilité '%s' sur l'application '%s'.\n";
            $msgDel = "Le groupe '%s' auquel vous êtes rattaché n'est plus habilité '%s' sur l'application '%s'.\n";

            $application = Application::find($idApplication);

            $message = "Bonjour,\n\n";
            $message .= "Vos habilitations SINAPS v5 concernant l'application " . $application->nom . " ont été modifiées.\r\n\r\n";

            foreach($merge as $adg) {
                $monMessage = $msgDel;
                if($adg->ajout) {
                    $monMessage = $msgAdd;
                    $listeUser = ApplicationDuGroupe::where('Application_id', $idApplication)
                                                    ->where('Groupe_id', $adg->Groupe_id)
                                                    ->first();
                    $utilisateursDuGroupe = $listeUser->groupe->utilisateurs;
                }

                $groupe = Groupe::find($adg->Groupe_id);
                $profil = Profil::find($adg->Profil_id);
                $utilisateursDuGroupe = $groupe->utilisateurs;

                $monMsgOK = sprintf($monMessage,
                                $groupe->nom,
                                $profil->nom,
                                $application->nom);

                $msgGrpOK = sprintf($msgGrp, $groupe->getLigneInfo());

                // Pour chaque Utilisateur
                foreach($utilisateursDuGroupe as $UDG) {
                    $utilisateur = $UDG->utilisateur;

                    // Si user inactif on passe
                    if( $utilisateur->isActif != 1) {
                        continue;
                    }

                    $monMsgEntier = $message;
                    $monMsgEntier.= $monMsgOK;
                    $monMsgEntier.= $msgGrpOK;

                    // Destinataire : par défaut luser de test sdi il est configuré, sinon le mail de l'utilisateur
                    $toUser = SinapsApp::getConfigValue("email.notification.test", $utilisateur->email);
                    if($toUser !== $utilisateur->email) {
                        $monMsgEntier.= "\nTEST: Ce mail est normalement envoyé à '". $utilisateur->email . "'.\n";
                    }

                    // Ajout des groupes utilisateurs
                    $listeGroupe = UtilisateurDuGroupe::with('Groupe')
                                                      ->where('Utilisateur_id', $utilisateur->id)
                                                      ->get();
                    if ($listeGroupe !== NULL ) {
                        $monMsgEntier.= "\nPour rappel, les groupes auquels vous appartenez sont:\n";
                        $strGroupes='';
                        foreach ($listeGroupe as $groupe) {
                            $strGroupes .= " - ".$groupe->groupe->getLigneInfo() . "\n";
                        }
                        $strGroupes .= "\n";
                        $monMsgEntier .= $strGroupes;
                    }

                    $monMsgEntier.= SinapsApp::getConfigValue("email.notification.signature");

                    $retour = $this->mailService->envoyerMail( $toUser, $sujet, $monMsgEntier);
                }
            }

            return $retour;

        } catch (Exception $e) {
            throw $e;
        }
    }

}
