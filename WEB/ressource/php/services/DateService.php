<?php

/**
 * Utilitaires sur les dates
 *
 * @author djacques
 *
 */
class DateService {

    public function __construct() {
        $this->timeService = SinapsApp::make("TimeService");

    }

    /**
     * Convertie un timestamp au format "JJ/MM/AAAA HH:MM:SS"
     *
     * @param int $time
     * @return string
     */
    public function timeToFullDate($time) {
        $retour = date("d/m/Y H:i:s", $time);
        return $retour;
    }

    /**
     * Convertie un timestamp au format "AAAA-MM-DD HH:MM:SS"
     *
     * @param int $time
     * @return string
     */
    static public function timeToUS($time) {
        $retour = date("Y-m-d H:i:s", $time);
        return $retour;
    }

     /**
     * Convertie une date au format "YYYY-MM-JJ HH:MM:SS" en timestamp
     *
     * @param string $date
     * @return string
     */

    public function USFormatDateToTime( $date ) {

        if( is_numeric($date))
            return $date;

        $annee = $heure = $minute = $seconde = $mois = $jour = 0;

        list($anneeMoisJour, $heureMinuteSeconde) = explode(' ', $date);
        list($annee, $mois, $jour) = explode('-', $anneeMoisJour);
        list($heure, $minute, $seconde) = explode(':', $heureMinuteSeconde);

        return mktime($heure, $minute, $seconde, $mois, $jour, $annee);
    }
}
