<?php
/**
 * Formatte pour une sortie sur en HTML
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
 * @author Damien André <damien.andre@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogFormat.php";

class HtmlLogFormat implements LogFormat {
	
    protected $etapes = array();
    public static $formats = array( 
        "debug" => "<p style='margin:0'><span class='ui-corner-all' style='margin:2px; padding: 1px;background-color:blue;color:white;font-weight:bold'>DEBUG</span>%s</p>",
        "info" => "<p style='margin:0'><span class='ui-corner-all' style='margin:2px; padding: 1px;background-color:green;color:white;font-weight:bold'>OK</span>%s</p>",
        "warning" => "<p style='margin:0'><span class='ui-corner-all' style='margin:2px; padding: 1px;background-color:#FFB347;color:black;font-weight:bold'>%s</span></p>",
        "error" => "<p style='margin:0'><span style='margin:2px; padding: 1px; background-color:#CD4C22;color:white;font-weight:bold'>KO</span>%s</p>",
        "CRITICAL" => "<p style='margin:0'><span class='ui-corner-all' style='margin:2px; padding: 1px; background-color:#CD4C22;color:white'><b>CRITIQUE</b>  %s</span></p>"
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

        $nouveauMsg = "<li style='padding-bottom: 3px; padding-left:10px'>" . $nouveauMsg . "</li>";

        return $nouveauMsg;
    }

    /**
     * Stocke 
     * @param string $nomEtape le nom de l'étape qu'on souhaite commencer à suivre
     */
    public function debuterEtape($nomEtape="__DEFAULT__", $message="") {
        $this->etapes[] = $nomEtape;
        return "<ol style='list-style-type: none; margin-bottom: 0px; margin-top: 0px; padding-left: 10px;'><b>" . $message . "</b><ol style='list-style-type: none; margin-bottom: 0px; margin-top: 0px;padding-left:0px'>";
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

        return "<b>" . $message . "</b></ol></ol><br />";
    }

}
