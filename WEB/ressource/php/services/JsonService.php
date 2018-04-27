<?php
/**
 * Conversion Array <--> Json.
 *
 * PHP version 5
 *
 * Classe permet de générer des réponses au format suivant:
 * {
 *  success: true|false,
 *  code: code,
 *  payload: {
 *      ...
 *      ...
 *      ...
 *  }
 * }
 * 
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class JsonService {
    /**
     * Crée la réponse
     * 
     * @param Mixed  $payload la charge utile
     * @param String $success TRUE pour indiquer le succès de la requête
     * @param String $code    le code d'erreur éventuel
     * @return String la structure sérialisée au format Json
     */
    static public function createResponse($payload, $success=TRUE, $code="") {
        $response = array( "success" => $success,
                           "code" => $code,
                           "payload" => $payload);

        $retour = json_encode($response);
        return $retour;
    }

    /**
     * Opération inverse de createResponse.
     *
     * @param String $response la réponse reçue par le curl
     * @return Mixed une structure contenant la structure Json parsée
     */
    static public function parseResponse($response) {
        return json_decode($response, TRUE);
    }

    /**
     * Crée une réponse positive à partir des objets passés 
     * 
     * @param array $array la charge utile
     * @return string la structure sérialisée au format Json
     */
    static public function createResponseFromArray(array $array) {
        $retour = static::createResponse($array);
        return $retour;
    }

    /**
     * Retourne un message d'erreur $code
     * 
     * @param String $code Code d'erreur
     * @return string
     */
    static public function createErrorResponse($code, $why="") {
        $retour = static::createResponse($why, FALSE, $code);
        return $retour;
    }
}