<?php
/**
 * Ecrit les logs en mémoire (la gestion se fait par niveau de logs).
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogWriter.php";

class MemoryLogWriter implements LogWriter {
    /**
     * Le buffer servant à stocker les logs.
     *
     * Exemple array( "error" => array( "log1", "log2"), "info => array( "log3"))
     *
     * @var array
	 */
    protected $buffer = array();

    /**
	 * Récupère le nom du fichier ( = parametres[0] )
	 * 
     * @param string $nom        nom du logger
     * @param array  $parametres paramètres
	 */
    public function __construct($nom=NULL, array $parametres=NULL) {
    }

    /**
	 * Ecrit le log dans le buffer memoire
	 * 
     * @param string $niveau  le niveau de log
     * @param string $message le message à écrire
	 */
    public function write($niveau, $message) {
        if (array_key_exists($niveau, $this->buffer) === FALSE) {
            $this->buffer[$niveau] = array();
        }
        $this->buffer[$niveau][] = $message;
    }

    /**
     * Retourne les logs correspond au niveau demandé
     * 
     * @param string $niveau: le niveau de log 
     * @return [array] les logs
     */
    public function dump($niveau) {
        if (array_key_exists($niveau, $this->buffer)) {
            return $this->buffer[$niveau];
        }

        return NULL;
    }

    public function flush() {
        $this->buffer = array();
    }

}