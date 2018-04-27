<?php
/**
 * Classe d'exception pour les accès BDD.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class OrmException extends Exception {
    /**
     * Constructeur.
     *
     * @param string $why raison de l'exception
     */
    public function __construct($why) {
        $this->message = $why;
    }

    /**
     * Sortie humainement compréhensible
     */
    public function toString() {
        return $this->message;
    }
}