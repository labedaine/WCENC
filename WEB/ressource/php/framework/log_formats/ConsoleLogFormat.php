<?php
/**
 * Formatte pour une sortie sur une console UNIX.
 * 
 * Logs standard: 
 *     Remplace [info] par un texte vert
 *     Remplace [error] par un texte rouge
 *     CRITICAL devient une ligne rouge
 *     
 * Etapes: Affiche le nom de l'étape et des espaces pour les logs à suivre.
 *
 * Options: aucune
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogFormat.php";

class ConsoleLogFormat implements LogFormat {
    protected $etapes = array();
    public static $formats = array( 
        "debug" => "%s",
        "info" => "\033[0;32mOK\033[0m %s",
        "warning" => "\033[0;33m%s\033[0m",
        "error" => "\033[0;31mKO %s\033[0m",
        "CRITICAL" => "\033[1;37;41mCRITIQUE %s\033[0m\033[K"
    );

    /**
     * Pas d'options
     * 
     * @param string $nom        le nom du logger
     * @param array  $parametres liste de paramètres
     */
    public function __construct($nom=NULL, array $parametres=array()) {
    }

    /**
     * Formate en couleur.
     * 
     * @param string $niveau  le niveau de log
     * @param string $message le message initial
     * @return [String] le message enrichit
     */
    public function format($niveau, $message) {
        $formatting = static::$formats[$niveau];
        $nouveauMsg = sprintf($formatting, $message);

        $nouveauMsg = str_repeat('  ', count($this->etapes)) . $nouveauMsg;

        return $nouveauMsg;
    }

    /**
     * Stocke 
     * @param string $nomEtape le nom de l'étape qu'on souhaite commencer à suivre
     */
    public function debuterEtape($nomEtape="__DEFAULT__", $message="") {
        $this->etapes[] = $nomEtape;
        return str_repeat('  ', count($this->etapes) - 1) . "\033[1m" . $message . "\033[0m";
    }

    /**
     * Enrichit 
     * @param string $message  le message initial
     * @param string $nomEtape le nom de l'étape qu'on souhaite terminer
     * @return [String] le message enrichit
     */
    public function finirEtape($message=NULL, $nomEtape=NULL) {
        $pos = array_search($nomEtape, $this->etapes);
        if ($pos !== FALSE) {
            unset($this->etapes[$pos]);
        }

        return str_repeat('  ', count($this->etapes)) . $message;
    }

}