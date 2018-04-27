<?php
/**
 * Classe générique gérant l'accès aux services d'une application.
 * 
 * Inspirée de Laraval 4: @see four.laravel.com
 * 
 * Composée uniquement de méthodes statiques.
 * 
 * Il est nécessaire d'appeler initialise avant toute utilisation
 * 
 * Exemple:
 *      App::bind( "Exemple", function() {
 *          return new Exemple();
 *      });
 *   
 *      $unExemple = App::make("Exemple");
 * 
 * Les principales fonctions gérées sont:
 *  - Injection de dépendance @see make @see bind @see singleton
 *  - Inscription de composants application: @see register @see FilterableHook
 *  - Gestion de la sortie violente de l'application: @see abort
 *  
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../vendor/Pimple.php";

class App {
    /**
     * Instance de pimple gérant l'injection de dépendance.
     * 
     * @var Pimple
     */
    static $diContainer;

    public static $sqlDialect = "POSTGRESQL";

    /**
     * initiatise l'application
     */
    public static function initialise() {
        static::$diContainer = new Pimple();
    }

    /**
     * Retourne un object du type $name.
     * 
     * Le type doit préalablement avoir été déclaré à l'aide des méthodes singleton ou register
     * 
     * @param String $name le type d'objet attendu
     * @return Object un object du type désiré
     */
    public static function make($name) {
        if (!static::$diContainer->offsetExists($name)) {
            // @TODO: log framework error
            print "SinapsApp: impossible de contruire $name\n";
        }
        return static::$diContainer[$name];
    }

    /**
     * Déclaration d'un type pour l'injection.
     * 
     * Contrairement à register, un nouvel objet est créé à chaque appel de make
     * 
     * @param String  $name    le nom de type 
     * @param Closure $closure la fonction de création de l'objet
     */
    public static function bind($name, Closure $closure) {
        static::$diContainer[$name] = $closure;
    }

    /**
     * Retourne un objet du type $name.
     * 
     * L'appel n'est fait qu'une fois et c'est toujours le même objet qui est retourné par la suite
     * 
     * @param String  $name    le nom du type
     * @param Closure $closure la fonction de création de l'objet
     */
    public static function singleton($name, Closure $closure) {
// DJS        static::$diContainer->offsetUnset($name);
        static::$diContainer[$name] = static::$diContainer->share($closure);

    }

    /**
     * Déclare un service dans l'application.
     * 
     * Charge automatiquement le fichier php commun/php/services/{Service}.php
     * 
     * @param String $serviceClass: le nom de la classe de service
     * @return void
     */
    public static function register($serviceClass) {
        static::bind(
            $serviceClass, 
            function () use ($serviceClass) {
                if (!class_exists($serviceClass))
                    include_once __DIR__ . "/../services/$serviceClass.php";

                return new $serviceClass();
            }
        );
    }

    public static function registerSingleton($serviceClass) {
        static::singleton(
            $serviceClass, 
            function () use ($serviceClass) {
                if (!class_exists($serviceClass))
                include_once __DIR__ . "/../services/$serviceClass.php";

                return new $serviceClass();
            }
        );
    }
    
    /**
     * Déclare un logger (de type Log2) dans l'application.
     * 
     * @param string $serviceClass nom dans l'injection de dépendance
     * @param string $loggerStr    le nom du logger (voir Log2)
     */
    public static function registerLogger($serviceClass, $loggerStr) {
        static::singleton(
            $serviceClass, 
            function () use ($serviceClass, $loggerStr) {
                    return new Log2($loggerStr);
            }
        );
    }

    /**
     * Enregistre un filtre dans l'application
     * 
     * Il est accessible par l'injecteur en faisant make("filter:{FilterName}")
     * 
     * @param string $filterName  le nom du filtre (alias du nom réel)
     * @param string $filterClass la classe de filtre
     * @return unknown
     */
    public static function filter($filterName, $filterClass) {
        static::bind(
            "filter:$filterName", 
            function () use ($filterClass) {
                    return new $filterClass();
            }
        );
    }

    /**
     * Renvoie un code http d'erreur.
     * 
     * @param int    $httpCode    le code http
     * @param string $httpMessage message à ajouter à la réponse
     */
    public static function abort($httpCode, $httpMessage="") {
        Response::abort($httpCode, $httpMessage);
    }
}
