<?php
/**
 * Formater de base.
 * 
 * Se contente d'ajouter [$niveauDuLog] devant le message
 * ou niveau est "error", "warning" ...
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogFormat.php";

class BaseLogFormat implements LogFormat {
    /**
     * ne fait rien de bien spécial
     * 
     * @param string $nom        le nom du logger
     * @param array  $parametres liste de paramètres
     */
    public function __construct($nom=NULL, array $parametres=array()) {
    }

    /**
     * Enrichit le message en ajoutant le niveau de log entre [] dans le message.
     *
     * @param string $niveau  le niveau de log
     * @param string $message le message initial
     * @return string le message enrichit
     */
    public function format($niveau, $message) {
        $resultat = "[$niveau] $message";

        return $resultat;
    }

    /**
     * Ne fait rien de spécial
     * @param string $nomEtape le nom de l'étape qu'on souhaite commencer à suivre
     */
    public function debuterEtape($nomEtape=NULL, $message="") {
    }

    /**
     * Enrichit le message en ajoutant le niveau de log entre []  et ***nomEtape*** dans le message.
     *
     * @param string $message  le message initial
     * @param string $nomEtape le nom de l'étape qu'on souhaite terminer
     * @return [String] le message enrichit
     */
    public function finirEtape($message=NULL, $nomEtape=NULL) {
        if ($nomEtape) {
            $message = "***$nomEtape*** $message";
        }

        $message = "[info] $message";
        return $message;
    }
}