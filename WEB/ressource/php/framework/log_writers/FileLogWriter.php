<?php
/**
 * Ecrit les logs dans un fichier.
 *
 * Utiliser le fichier .ini pour définir le fichier de sortie
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogWriter.php";

class FileLogWriter implements LogWriter {
    /**
	 * Le fullpath du fichier à écrire.
     *
     * @var [string]
	 */
    protected $nomFichierAvecChemin;

    /**
	 * Récupère le nom du fichier ( = parametres[0] )
	 * 
     * @param String $nom        nom du logger
     * @param array  $parametres paramètres
	 */
    public function __construct($nom=NULL, array $parametres=array()) {
        if (count($parametres) === 0) {
            throw new Exception("FileLogWriter:le chemin complet du fichier de log est obligatoire");
        }
        $this->nomFichierAvecChemin = $parametres[0];
    }

    /**
     * Ecrit le log dans le fichier.
	 * 
     * @param string $niveau  le niveau de log
     * @param string $message le message à écrire
	 */
    public function write($niveau, $message) {
        file_put_contents($this->nomFichierAvecChemin, $message . "\n", FILE_APPEND);
    }

    /**
     * Ne fait rien
     * 
     * @param string $niveau: le niveau de log 
     * @return [NULL] retourne toujours NULL
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