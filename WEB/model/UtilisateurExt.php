<?php
    /**
     * Extension pour l'utilisateur
     *
     * PHP version 5
     *
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class UtilisateurExt extends SinapsModel {


    /**
     * Constantes pour le type de groupe
     */

    const PSE = 1;
    const ISE = 2;
    const ISC = 3;
    const CSP = 4;
    const TG  = 5;
    const ENSEIGNANT = 6;
    const ACTIVE_USER_VALUE = 1; 
    
    // Correspondance
    public static function numToString($num) {

        switch($num) {
            case 1: return "PSE"; break;;
            case 2: return "ISE"; break;;
            case 3: return "ISC"; break;;
            case 4: return "CSP"; break;;
            case 5: return "TG"; break;;
            case 6: return "ENSEIGNANT"; break;;
            default: return "Inconnu"; break;;
        }

        return "Inconnu";
    }

}
