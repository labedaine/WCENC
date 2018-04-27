<?php
/**
 * Jete tous les logs à la poubelle.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogWriter.php";

class NullLogWriter implements LogWriter {

    /**
     * Ne fait rien de spécial
     * @param string $nom        nom du logger
     * @param array  $parametres paramètres
     */
    public function __construct($nom=NULL, array $parametres=NULL) {
    }

    /**
     * Ne fait rien de spécial
     * @param string $niveau  le niveau de log
     * @param string $message le message à écrire
     */
    public function write($niveau, $message) {
    }

    /**
     * Ne fait rien
     * 
     * @param string $niveau le niveau de log 
     * @return null retourne toujours null
     */
    public function dump($niveau) {
        return NULL;
    }

    /**
     * Ne fait rien
     * 
     * @return null retourne toujours null
     */
    public function flush() {
        return NULL;
    }

}