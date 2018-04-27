<?php
/**
 * Ecrit les logs dans la console pour les scripts.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogWriter.php";

class ConsoleLogWriter implements LogWriter {

    /**
     * ne fait rien de spécial
     * @param string $nom        nom du logger
     * @param array  $parametres paramètres
     */
    public function __construct($nom=NULL, array $parametres=NULL) {
    }

    /**
     * Sort le message sur la console
     * @param string $niveau  le niveau de log
     * @param string $message le message à écrire
     */
    public function write($niveau, $message) {
        print "$message\n";
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
     * @param string $niveau le niveau de log 
     * @return null retourne toujours null
     */
    public function flush() {
        return NULL;
    }

}