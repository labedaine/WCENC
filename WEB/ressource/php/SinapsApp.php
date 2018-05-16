<?php
/**
* Surcharge de PDO essayant de se reconnecter N fois en cas de problème d'accès à la BDD.
*
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/

class SinapsApp extends App {
    /**
     * Les valeurs lues dans le(s) fichier(s) .ini.
     *
     * @var array
     */
    static $config = array();
    /**
     * L'utilisateur actuellement connecté si il existe.
     *
     * @var Utilisateur
     */
    static $utilisateurCourant;

    /**
     * Namespace PHP pour les classes de l'ORM.
     *
     * @var string
     */
    public static $dataNamespace;

    /**
     * Initialise une application SINAPS
     *
     * Lie le fichier sinaps.ini
     * Init de la BDD
     * Init des services
     * Init des filtres
     *
     * @param string $cheminConig Chemin du dossier Config
     */
    public static function initialise($cheminConfig=NULL, $namespace="") {
        App::initialise();

        static::$config = ConfigReaderService::readConfig($cheminConfig);

        static::initDb();
        static::registerServices();
        static::registerFilters();

        static::$dataNamespace = $namespace;
    }

    /**
     * Déclare les services disponibles
     */
    public static function registerServices() {

        static::register("JsonService");
        static::registerSingleton("TimeService");
        static::registerSingleton("RestClientService");
        static::register("DateService");
        static::register("MailService");
        static::register("FileService");
        static::register("SystemService");
        static::register("LoginService");
        static::register("ParisService");
        static::register("Log");
    }

    /**
     * Déclare les filtres disponibles
     */
    protected static function registerFilters() {
        static::filter("authentification", "AuthentificationFilter");
    }

    /**
     * Init la BDD en fonction des paramètres de config
     * @return PDO|NULL
     */
    public static function initDb() {
        static::singleton(
            "dbConnection",
            function () {
                $config = SinapsApp::$config;
                if ( array_key_exists("db_type", $config)) {
                    $dbh = new ReconnectPDO(
                        $config["db_type"] .
                        ":host=" . $config["db_host"] .
                        ";dbname=" .$config["db_name"],
                        $config["db_user"],
                        $config["db_pass"],
                        array(
                            PDO::ATTR_PERSISTENT => FALSE
                        )
                    );

                    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $dbh;
                }

                return NULL;
            }
        );
    }

    /**
     * Fournit la valeur d'une variable de configuration en fonction de son nom
     * @param string $configName    le nom de la variable de configuration
     * @param Mixed  $fallbackValue la valeur à retourner si la clef n'existe pas
     * @return la valeur associ�e � configName dans le fichier de configuration
     */
    public static function getConfigValue($configName, $fallbackValue=NULL) {
        if (array_key_exists($configName, static::$config)) {
           return static::$config[$configName];
        }

        return $fallbackValue;
    }

    public static function getErrorMsg() {
        $params = func_get_args();
        $key = "msg.erreur." . array_shift($params);


        $texte = static::getConfigValue($key);
        if (!$texte)
            $texte = "Aucun message d'erreur associé à la clef $key";
        else
            $texte = vsprintf($texte, $params);

        return $texte;
    }

    /**
     * Réinitialise tous les champs statiques.
     *
     * Nécessaire pour les tests
     */
    public static function reset() {
        static::$utilisateurCourant = NULL;
        Input::reset();
    }

    public static function reloadConfig($cheminConfig) {
        ConfigReaderService::deleteInstance();
        static::$config = ConfigReaderService::readConfig($cheminConfig);
    }

    /**
     * Retourne l'utilisateur connecté
     *
     * @return Mixed l'objet utilisateur
     */
    public static function utilisateurCourant() {
        return static::$utilisateurCourant;
    }

    /**
     * Affecte l'utilisateur courant
     *
     * @param Mixed $user l'utilisateur
     */
    public static function setUtilisateurCourant($user) {
        static::$utilisateurCourant = $user;
    }
}
