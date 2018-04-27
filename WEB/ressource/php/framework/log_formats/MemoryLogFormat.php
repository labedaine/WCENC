<?php
/**
 * Format ajoutant les informations de consommation mémoire.
 * 
 * Logs standard: Ajoute Mem:xxxx devant le message
 * Etapes: Ajoute DeltaMem:xxxx devant le message
 *
 * Options: noFormat => renvoie le nombre d'octets. Sinon un formattage en Kb..Mb etc.. est effectué
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogFormat.php";

class MemoryLogFormat implements LogFormat {
    protected $sizes = array();
    protected $prettyPrint = TRUE;

    static $unit = array('b','Kb','Mb','Gb','Tb','Pb');

    /**
     * lecture de l'option noFormat
     * 
     * @param string $nom        le nom du logger
     * @param array  $parametres liste de paramètres
     */
    public function __construct($nom=NULL, array $parametres=array()) {
        if (array_search("noFormat", $parametres) !== FALSE) {
            $this->prettyPrint = FALSE;
        }
    }

    /**
     * Enrichit le message en ajoutant Mem:<consommation mémoire>
     * @param string $niveau  le niveau de log
     * @param string $message le message initial
     * @return [String] le message enrichit
     */
    public function format($niveau, $message) {
        $mem = memory_get_usage(TRUE);
        if ($this->prettyPrint) {
            $mem = $this->convert($mem);
        }

        $resultat = "Mem:$mem $message";

        return $resultat;
    }

    /**
     * Stocke le conso mémoire à l'instant T
     * @param string $nomEtape le nom de l'étape qu'on souhaite commencer à suivre
     */
    public function debuterEtape($nomEtape="__DEFAULT__", $message="") {
        $this->sizes[$nomEtape] = memory_get_usage(TRUE);
    }

    /**
     * Enrichit le message en ajoutant DeltaMem:<delta mémoire depuis debuterEtape>
     * @param string $message  le message initial
     * @param string $nomEtape le nom de l'étape qu'on souhaite terminer
     * @return [String] le message enrichit
     */
    public function finirEtape($message=NULL, $nomEtape=NULL) {
        $tailleInitiale = ($nomEtape) ? $this->sizes[$nomEtape] : $this->sizes["__DEFAULT"];
        $delta = memory_get_usage(TRUE) - $tailleInitiale;

        if ($this->prettyPrint) {
            $delta = $this->convert($delta);
        }

        $message = "DeltaMem:" . $delta . " $message";

        return $message;
    }

    /**
     * Converti un nb d'octet en unité de plus grosse quantité (Kb/Mb...)
     *
     * @param int $size la taille en octet
     * @return string la taille formatée
     */
    public function convert($size) {
        $signe = "";
        if($size < 0 )
            $signe = "-";
        $size = abs($size);

        if ($size === 0) return "0 b";

        $return = @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.static::$unit[$i];
        return $signe.$return;
    }

}