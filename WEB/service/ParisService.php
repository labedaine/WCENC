<?php
/**
 * Ensemble de fonctions liées à l'identification.
 *
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/


class ParisService {

    // Variable liées au curl

    public function __construct() {
    }

    public function getUtilisateurDepuisToken($token) {

        $session = Paris::all;

        if ($session === NULL) {
            return NULL;
        }

        return $session->utilisateur;
    }

}
