<?php
/**
 * Classe gérant les routes (lien entre une url et un controleur).
 * 
 * Une route se déclare dans le fichier route.php
 * La structure est la suivante:
 * 
 * Route::<httpVerb>( $regexp, "<Controller>@<action>");
 * 
 * Exemple: Route::get( "^/alertes", "AlerteController@getListe");
 * Exemple: Route::get( "^/alerte/(\d+)/commentaires", "AlerteController@getCommentaires");
 * 		=> Répond à /alerte/3/commentaires
 * 		=> 3 étant l'ID de l'alerte.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Route {
    // @var array<regexp => controller+Action> $routes: liste des routes enregistrées
    protected static $routes = array(   "GET" => array(),
                                        "POST" => array(),
                                        "PUT" => array(),
                                        "DELETE" => array());

    /**
     * Fonction Resolve.
     *
     * A partir d'une URL (fournie par index.php) appele le controller et l'action concernée
     * Note: sort immédiatement après qu'une URL est matchée
     * 
     * @param String $url: l'url demandée par l'utilisateur
     * @return true si au moins 1 action à matchée
     */
    static function resolve($url) {
        $matches = array();

        $httpVerb = Request::getHttpVerb() ? : "GET";

        foreach( static::$routes[$httpVerb] as $regexp => $controllerAction) {
            if (preg_match("#".$regexp."#", $url, $matches) === 1) {
                $splitted = explode("@", $controllerAction);

                $controllerName = $splitted[0];
                $action = $splitted[1];

                if (class_exists($controllerName) === FALSE) {
                    if (file_exists("controllers/$controllerName.php"))
                        include "controllers/$controllerName.php";
                    else if (file_exists(__DIR__ . "/../controllers/$controllerName.php"))
                        include __DIR__ . "/../controllers/$controllerName.php";
                }

                $controller = new $controllerName();
                Response::addContent($controller->invoke($action, $matches));

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Ajoute une action sur un verbe http GET
     * 
     * @param String $regexp:           la regexp à matcher 
     * @param String $controllerAction: l'action à appeler sous la forme <ControllerName>@<actionName>
     */
    static function get($regexp, $controllerAction) {
        static::$routes["GET"][$regexp] = $controllerAction;
    }

    /**
     * Ajoute une action sur un verbe http POST
     * 
     * @param String $regexp:           la regexp à matcher 
     * @param String $controllerAction: l'action à appeler sous la forme <ControllerName>@<actionName>
     */
        static function post($regexp, $controllerAction) {
        static::$routes["POST"][$regexp] = $controllerAction;
    }

    /**
     * Ajoute une action sur un verbe http PUT
     * 
     * @param String $regexp:           la regexp à matcher 
     * @param String $controllerAction: l'action à appeler sous la forme <ControllerName>@<actionName>
     */
        static function put($regexp, $controllerAction) {
        static::$routes["PUT"][$regexp] = $controllerAction;
    }

    /**
     * Ajoute une action sur un verbe http DELETE
     * 
     * @param String $regexp:           la regexp à matcher 
     * @param String $controllerAction: l'action à appeler sous la forme <ControllerName>@<actionName>
     */
    static function delete($regexp, $controllerAction) {
        static::$routes["DELETE"][$regexp] = $controllerAction;
    }

    static function reset() {
        static::$routes = array(   "GET" => array(),
                           "POST" => array(),
                           "PUT" => array(),
                           "DELETE" => array());
    }
}