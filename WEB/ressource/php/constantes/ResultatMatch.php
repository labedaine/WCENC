<?php
    /**
     * Classe de constantes utilisables par tous les modules.
     *
     * PHP version 5
     *
     * @author Damien
     */

class ResultatMatch {

    const UN_GAGNE = 1;
    const NUL     = 0;
    const DEUX_GAGNE = 2;

    // Score

    private static $nomStatut = array(
        self::UN_GAGNE => "L'équipe 1 a gagné",
    );

    public static function resultatToString($status) {
        if (array_key_exists($status, self::$nomStatut))
            return self::$nomStatut[$status];
        else
            return "Statut inconnu";
    }
}
