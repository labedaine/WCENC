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
    protected $restClientService = NULL;
    
    protected $ouSuisJe = NULL;

    private $namespace = "";
    private $classSession;

    public function __construct() {
        // On charge les paramètres de configuration depuis sinaps.ini
        $this->timeoutCurl       = SinapsApp::getConfigValue("TimeoutCurl");
        
        $this->dateService       = App::make("DateService");
        $this->fileService       = App::make("FileService");
        $this->restClientService = App::make("RestClientService");
        
        var_dump(SinapsApp::$config);
 
        $this->classSession = SinapsApp::$dataNamespace . "\Session";
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

        if(in_array($this->ouSuisJe, array("configuration", "deploiement_sondes", "formation"))) {
            $retour = $this->getLoginConfiguration($username, $password);
            
        } else if($this->ouSuisJe === "restitution") {
            $retour = $this->getLoginRestitution($username, $password);
        }
        return $retour;
    }
    
    /**
     * Retourne l'utilisateur à conf si le couple login/pass est valide, le code erreur sinon
     *
     * @param string $username le nom de l'utilisateur
     * @param string $password le mot de passe utilisateur
     * @return boolean TRUE si le login est ok, "401" si non ok, "402" si pas de droits
     */
    
    public function loginDepuisConf($username, $password) {

        $user = Utilisateur::where("login", $username)->first();

        if ( $user && $user->password === $password) {
            if( !$user->isActif) {
                return 401;
            }

            if( ($user->isActif) && (count($user->profilsDeLUtilisateur) === 0) ) {
                return 402;
            }
            
            // Il faut qu'il soit administrateur
            foreach($user->profilsDeLUtilisateur as $profil) {
                if($profil->Profil_id == 5) {
                    return $user;
                }
            }
            return 403;
        } else {
            return 401;
        }
    }

    /**
     * Retourne l'utilisateur à formation si le couple login/pass est valide, le code erreur sinon
     *
     * @param string $username le nom de l'utilisateur
     * @param string $password le mot de passe utilisateur
     * @return boolean TRUE si le login est ok, "401" si non ok, "402" si pas de droits
     */
    
    public function loginDepuisFormation($username, $password) {

        $user = Utilisateur::where("login", $username)->first();

        if ( $user && $user->password === $password) {
            if( !$user->isActif) {
                return 401;
            }

            if( ($user->isActif) && (count($user->profilsDeLUtilisateur) === 0) ) {
                return 402;
            }

            // Il faut qu'il soit administrateur
            foreach($user->profilsDeLUtilisateur as $profil) {
                if($profil->Profil_id == 5 || $profil->Profil_id == 2) {
                    return $user;
                }
            }

            return 403;
        } else {
            return 401;
        }
    }

    private function getLoginConfiguration($username, $password) {
        
        $class = "\models\configuration\Utilisateur";
        $this->url     = SinapsApp::getConfigValue("restitution.hasRightToConnect");
        $this->srvApp  = SinapsApp::getConfigValue("restitution.hostname");

        $url = "http://" . $this->srvApp . $this->url;
        $param = array(
            "login"  => $username,
            "passwd" => $password
        );

        $status = array();
        try {
			
            $json = $this->restClientService->getURL($url, $param, FALSE, $this->timeoutCurl);
            $this->restClientService->throwExceptionOnError($json);          
            
        } catch (SinapsException $exc) {
            return $exc->getCode();
        }
        
        $response = json_decode($json, FALSE);
        
        $payload = $response->payload;
        $unser = unserialize($payload);

        $user 	= $unser['utilisateur'];
        $user->id = $unser['id'];
        $droits = $unser['droits'];
   
		$user->insertOrUpdate();

		// on s'authentifie
		$perform = $this->performLogin($user);
		
		$retour = array();
		$retour['login'] = $user->login;
		$retour['id'] = $user->id;
		$retour['droits'] = $droits;
        return $retour;
    }

    private function getLoginRestitution($username, $password) {
                
        $user = Utilisateur::where("login", $username)->first();

        if ( $user && $user->password === $password) {
            if( !$user->isActif) {
                return 401;
            }
            
            // Si l'utilisateur a la préférence "Gestion Habilitations", il peut se connecter
//            $prefGestionHabilitation = UtilisateurPreference::where('Utilisateur_id', $user->id)
//                                                        ->where('clef', 'droitsSpecifiques')
//                                                        ->where('valeur', 'gestionHabilitations')
//                                                        ->get();
//            if( ($user->isActif) && (count($prefGestionHabilitation) === 0) ) {
                if( ($user->isActif) && (count($user->profilsDeLUtilisateur) === 0) ) {
                    return 402;
                }
//            }

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
        $classSession = $this->classSession;

        $classSession::where("Utilisateur_id", $utilisateur->id)->delete();
        return TRUE;
    }

    /**
     * Récupère les information de l'utilisateur à partir de son "token"
     *
     * @param string $token le token (fournit par le cookie en général)
     * @return Mixed l'utilisateur
     */

    public function getUtilisateurDepuisToken($token) {
        $classSession = $this->classSession;

        $session = $classSession::where("token", $token)->first();

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
        $classSession = $this->classSession;

        // Generation du token
        $token = md5(uniqid("bhlsde".$user->login, TRUE));

        // Save session info to DB ...
        // @TODO: Utiliser le cache et/ou memcached
        $session = new $classSession();
        $session->token = $token;
        $session->date = App::make("TimeService")->now();
        
        $user->session()->save($session);
		
        SinapsApp::setUtilisateurCourant($user);
        Cookie::session(SinapsApp::$config['ou.suis.je']."_token", $token);
        
        return $user;
    }
}
