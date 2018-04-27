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
     * Convertie un timestamp au format "JJ/MM HH:MM" (utile pour commentaire)
     *
     * @param int $time
     * @return string
     */
    static public function timeToCommentFormatDate($time) {
        $retour = date("d/m H:i:s", $time);
        return $retour;
    }

    /**
     * Convertie un timestamp au format "JJ/MM/YY HH:MM" (utile pour alerte)
     *
     * @param int $time
     * @return string
     */
    static public function timeToAlerteFormatDate($time) {
        $retour = date("d/m/y H:i:s", $time);
        return $retour;
    }

    /**
     * Convertie un timestamp au format "JJ/MM/YYY HH:MM" (utile pour Service Manager)
     *
     * @param int $time
     * @return string
     */
    static public function timeToSMAFormatDate($time) {
        $retour = date("d/m/Y H:i", $time);
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

     /**
     * Convertie une date au format "JJ/MM HH:MM:SS" en timestamp (utile pour commentaire)
     *
     * @param string $date
     * @return string
     */

    public function commentFormatDateToTime( $date ) {

        $heure = $minute = $seconde = $mois = $jour = 0;

        list($jourMois, $heureMinuteSeconde) = explode(' ', $date);
        list($jour, $mois) = explode('/', $jourMois);
        list($heure, $minute, $seconde) = explode(':', $heureMinuteSeconde);

        return mktime($heure, $minute, $seconde, $mois, $jour);
    }

     /**
     * Convertie une date de n'importe quel format accepté en timetamp (utile pour commentaire)
     *
     * @param string $date      chaîne représentant la date à convertir
     * @param string $date      opérateur dans le cas d'une comparaison
      *                                     pour fixer les Heures minutes secondes dans le cas d'une date passée sans HMS
      *                                     - si '', ou "le" ou "gt" -> 23:59:59
      *                                     - si "lt" ou "ge" -> 00:00:00
     * @return string
     */

    public function allFormatDateToTimestamp( $date, $operateurPourComparaison = '' ) {

        // Heures:Minutes:Secondes par défaut
        // si le format de date n'inclut pas les Heures:Minutes:Secondes
        $heure = "23";
        $minute = "59";
        $seconde = "59";
        if (in_array($operateurPourComparaison, array('lt', 'ge'))) {
            $heure = "00";
            $minute = "00";
            $seconde = "00";
        }
        $mois = 0;
        $jour = 0;
        $annee = 0;

        // Cas jj/mm
        if( preg_match('/^[0-9]{2}\/[0-9]{2}$/', $date) !== 0 ) {
            list($jour, $mois) = explode('/', $date);
        }
        
        if( preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{2}$/', $date) !== 0 ) {
            list($jour, $mois, $annee) = explode('/', $date);
            $annee = $annee+2000;
        }

        // Format UNIX (AAAA-MM-JJ H:i:S)
        if( preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $date, $matches) !== 0 ) {
                // Info : Année ($matches[1] non utilisé)
                $mois = $matches[2];
                $jour = $matches[3];
                $heure = $matches[4];
                $minute = $matches[5];
                $seconde = $matches[6];
        // Cas autre
        } elseif( preg_match('/^[0-9]{2}\/[0-9]{2} [0-9]{2}/', $date) !== 0 ) {

            list($jourMois, $hMnSec) = explode(' ', $date);
            list($jour, $mois) = explode('/', $jourMois);
            $heureMinuteSeconde = explode(':', $hMnSec);
            $count = count($heureMinuteSeconde);
            for($i=0;$i< $count;$i++ ) {
                switch($i) {
                    case 0:
                        $heure = $heureMinuteSeconde[$i];
                        break;
                    case 1:
                        $minute = $heureMinuteSeconde[$i];
                        break;
                    case 2:
                        $seconde = $heureMinuteSeconde[$i];
                        break;
                    default:
                        break;
                }
            }
        }  elseif( preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{2} [0-9]{2}/', $date) !== 0 ) {

            list($jourMoisAnnee, $hMnSec) = explode(' ', $date);
            list($jour, $mois, $annee) = explode('/', $jourMoisAnnee);
            $annee = $annee + 2000;
            $heureMinuteSeconde = explode(':', $hMnSec);
            $count = count($heureMinuteSeconde);
            for($i=0;$i< $count;$i++ ) {
                switch($i) {
                    case 0:
                        $heure = $heureMinuteSeconde[$i];
                        break;
                    case 1:
                        $minute = $heureMinuteSeconde[$i];
                        break;
                    case 2:
                        $seconde = $heureMinuteSeconde[$i];
                        break;
                    default:
                        break;
                }
            }

        }

        // conversion de la date en timestamp avec prise en compte du changement d'année
        $maintenant = $this->timeService->now();
        $anneeCourante = date("Y", $maintenant);
        $moisCourant = date("m", $maintenant);
        $jourCourant = date("d", $maintenant);
        // SI le mois choisi est inférieur au mois courant ET la date jour choisi est inférieure au jour courant
        if($annee !== 0) {
				$retour = mktime($heure, $minute, $seconde, $mois, $jour, $annee);
		} else if (($mois < $moisCourant) && ($jour < $jourCourant)) {
			// ALORS l'année choisie est l'année suivante
            $retour = mktime($heure, $minute, $seconde, $mois, $jour, ($anneeCourante+1));
        } else {
            // SINON l'année choisie est l'année courante.
            $retour = mktime($heure, $minute, $seconde, $mois, $jour, $anneeCourante);
        }
        return $retour;
    }

    /**
    * Cette fonction retourne true si HO ou false si HNO (correspondant aux jours fériés en France).
    *
    * @param $date
    * @return boolean
    */
    public function isHO($timestamp = null)
    {
        if(!$timestamp) {
            $timestamp = $this->timeService->now();
        }
        // Rappel: mktime(heure, minute, seconde, mois, jour, année)

        // Gestion des heures HO
        $plageHoraire = SinapsApp::$config['commun.plage.HO'];
        $plageHoraire = explode("-", $plageHoraire);

        $debutHO = mktime(  substr($plageHoraire[0], 0, 2),
                            substr($plageHoraire[0],-2),
                            0,
                            date('n', $timestamp),
                            date('j', $timestamp),
                            date('Y', $timestamp));

        $finHO = mktime(  substr($plageHoraire[1], 0, 2),
                          substr($plageHoraire[1],-2),
                          0,
                          date('n', $timestamp),
                          date('j', $timestamp),
                          date('Y', $timestamp));

        $jourTimestamp = strtotime(date('m/d/Y', $timestamp));

        //$date = strtotime(date('m/d/Y H:i',$date));
        $year = date('Y',$timestamp);

        $easterDate  = easter_date($year);
        $easterDay   = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear   = date('Y', $easterDate);

        $holidays = array(
            // Dates fixes
            mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
            mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
            mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
            mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
            mktime(0, 0, 0, 8,  15, $year),  // Assomption
            mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
            mktime(0, 0, 0, 11, 11, $year),  // Armistice
            mktime(0, 0, 0, 12, 25, $year),  // Noel

            // Dates variables
            mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear), // Paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear), // Ascension
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear), // Pentecote
        );

        // Gestion des Week-End
        $weekEnd = date('N', $timestamp);

        // On traite les cas par ordre de priorité
        if(in_array($jourTimestamp, $holidays))
            return FALSE;

        if($weekEnd >= 6)
            return FALSE;

        if( $timestamp > $debutHO && $timestamp < $finHO )
            return TRUE;
        else
            return FALSE;
    }
}
