<?php
/**
 * Permet de commander directement à la réponse HTTP.
 * 
 * Utilisable pour envoyer un code d'erreur HTTP (code différent de 200)
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Response {
    /**
     * Sort immédiatement et violemment avec le code spécifié
     * 
     * @param string $httpCode    code HTTP
     * @param string $httpMessage message
     */
    static public function abort($httpCode, $httpMessage) {
        header(':', TRUE, $httpCode);
        print "$httpMessage";
    }

    /**
    * Ajoute un texte au contenu de la réponse
    *
    * @param string $texte le texte
    */
    static public function addContent($texte) {
        print $texte;
    }
}