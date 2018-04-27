<?php
/**
 * Format ajoutant les informations d'heure courante.
 * 
 * Logs standard: Ajoute @<date> devant le message
 * Etapes: Ajoute DeltaTemps:xxxx devant le message
 *
 * Options: 
 *  0: format de la date (defaut: d/m/Y h:i:s)
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/LogFormat.php";

class TimeLogFormat implements LogFormat {
    /**
     * Encapsule l'horloge.
     * 
     * @var TimeService
     */
    protected $timeService;
    /**
     * Le format de date à appliquer.
     * 
     * @var string
     */
    protected $format = '[Y-m-d H:i:s]';
    /**
     * Stocke les dates début des étapes (format "nomEtape" => <timestamp de début d"étape>).
     * 
     * @var array
     */
    protected $timestamps = array();

    /**
     * lecture de l'option 0: format de la date
     * 
     * @param string $nom        le nom du logger
     * @param array  $parametres liste de paramètres
     */
    public function __construct($nom=NULL, array $parametres=array()) {
        if (count($parametres) >= 1) {
            $this->format = $parametres[0];
        }

        $this->timeService = SinapsApp::make("TimeService");
    }

    /**
     * Enrichit le message en ajoutant @<date>
     * @param string $niveau  le niveau de log
     * @param string $message le message initial
     * @return string le message enrichit
     */
    public function format($niveau, $message) {
        $resultat = date($this->format, $this->timeService->now()) . "\t". $message;

        return $resultat;
    }

    /**
     * Stocke la date de départ de l'étape
     * @param string $nomEtape le nom de l'étape qu'on souhaite commencer à suivre
     */
    public function debuterEtape($nomEtape="__DEFAULT", $message="") {
        $this->timestamps[$nomEtape] = $this->timeService->now();
    }

    /**
     * Enrichit le message en ajoutant DeltaTemps:<delta en secondes depuis debuterEtape>
     * @param string $message  le message initial
     * @param string $nomEtape le nom de l'étape qu'on souhaite terminer
     * @return [String] le message enrichit
     */
    public function finirEtape($message=NULL, $nomEtape=NULL) {
        $timestampInitial = ($nomEtape) ? $this->timestamps[$nomEtape] : $this->timestamps["__DEFAULT"];
        $delta = $this->timeService->now() - $timestampInitial;

        $message = "DeltaTemps:" . $delta . " $message";

        return $message;
    }
}