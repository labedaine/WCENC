<?php
/**
 * Permet de récuperer les informations par rapport à la requete HTTP.
 *
 * Permet de récuperer le verbe HTTP utilisé lors de la requete:  GET POST PUT DELETE
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Request {
    /**
     * Retourne le verbe HTTP utilisé par le client: GET POST PUT DELETE.
     * 
     * Si un paramètre METHOD a été déclaré lors de la requete c'est lui qui prend la priorité
     *      => c'est utile pour pouvoir tester les appels depuis un navigateur 
     * 
     * @return le verbe
     */
    public static function getHttpVerb() {
        // Pour faciliter les tests possibilité de passer la verve http en paramètre
        if ( Input::get("METHOD", "NOT SET") !== "NOT SET") {
            return INPUT::get("METHOD");
        }
        
        return $_SERVER['REQUEST_METHOD'];
    }
}
