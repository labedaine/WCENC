<?php
/**
 * Simple service représentant l'horloge.
 *
 * Son utilisation permet de simuler le temps qui passe lors des tests 
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class TimeService {
    function __construct() {}
    
    /**
     * Retourne le temps et heure actuel
     * 
     * @return number timestamp unix representant l'heure actuelle
     */
    public function now() {
        $retour = time();
        return $retour;
    }

    /** 
     * Retourne la date courant + ou - un delta
     * 
     * @param $delta: le delta au format strtotime
     * @return number timestamp unix representant l'heure actuelle
     */
    public function nowAndDelta($delta) {
        // @TODO: Add Test
        $strtotime = "now";
        switch ($delta) {
            case '12h':
                $strtotime = '-12 hour';
            break;

            case '24h':
                $strtotime = '-1 day';
            break;

            case '48h':
                $strtotime = '-2 day';
            break;

            case '7j':
                $strtotime = '-7 day';
            break;

            case '1m':
                $strtotime = '-1 month';
            break;

            default:
            break;
        }
        $retour = strtotime($strtotime);
        return $retour;
    }

    public function toJS($date) {
        $retour = $this->addTimeZoneOffsetJavascript($this->getTimestampFromDate($date.' GMT'));
        return $retour;
    }

    public function fromJS($jsDate) {
        $retour = ($jsDate / 1000) - $this->getTimeZoneOffsetEnSecondes();
        return $retour;
    }


     /**
     * Retourne un timestamp a partir d'une chaine date formaté
     * @param string $date Chaine représentant une date
     * @return timestamp 
     */
    public function getTimestampFromDate($date=null) {
        $retour = strtotime($date);
        return $retour;
    }

    public function getTimestampPlusTZFromDate($date=null) {
        $retour = $this->getTimestampFromDate($date) + $this->getTimeZoneOffsetEnSecondes();
        return $retour;
    }

    /**
     * Ajout $timezone_offset à $timestamp et retourne le résultat multiplié 
     * par 10000 (Format timestamp javascript)
     * @param timestamp $timestamp
     * @return timestampo
     */
    public function addTimeZoneOffsetJavascript($timestamp=0, $timezone_offset=null) {
        if ($timezone_offset === null) {
            $timezone_offset = $this->getTimeZoneOffsetEnSecondes();
        }
// print "*********************TIME: $timestamp********\n";
        $retour = ( $timestamp - $timezone_offset ) * 1000;
        return $retour;
    }

    public function getTimeZoneOffsetEnSecondes() {
        $this_tz_str = date_default_timezone_get();
        $this_tz = new DateTimeZone($this_tz_str);
        $now = new DateTime("now", $this_tz);
        $retour = $this_tz->getOffset($now);
        return $retour;
    }
    
    /**
     * Renvoie une liste de dates / heure comprises entre 2 dates 
     * en fonction d'une taille de créneau fournie
     * @param date $dateDebut Date de départ
     * @param date $dateFin Date de fin
     * @param integer $creneauEnSecondes Taille du créneau (en secondes)
     * @param string $ordre par défaut 'ASC' : tri de la liste renvoyée, si 'DESC', tri déscendant
     * @param boolean $ajouterDateFin Si vrai, ajoute la date de fin
     * @return array Renvoie une liste de dates triée par ordre ascendant ou descendant
     */
    public function renvoieListeCreneaux($dateDebut, $dateFin, $creneauEnSecondes = 60, $ordre = 'ASC', $ajouterDateFin = true) {
        $listeRetour = array($dateDebut);
        if ($dateFin > $dateDebut) {
            $objDateFin = new DateTime($dateFin);
            $intervalle = new DateInterval('PT'.$creneauEnSecondes.'S');
            while(true) {
                $derniereDateDeLaListe = new DateTime($listeRetour[count($listeRetour) -1]);
                // On ajoute l'intervalle
                $dateSuivante = $derniereDateDeLaListe->add($intervalle);
                $objDiff = date_diff($dateSuivante, $objDateFin);
                #$differenceAvecDateFin = ($objDiff->s + ($objDiff->i*60) +($objDiff->h*3600));
                // Signifie que la différence est positive
                if ($objDiff->invert === 0) {
                    $listeRetour[] = $dateSuivante->format('Y-m-d H:i:s');
                } else {
                    // En dernier, on ajoute la date de fin si le paramètre ajouterDateFin est à true
                    if ($ajouterDateFin) {
                        if ($objDateFin->format('Y-m-d H:i:s') !== $listeRetour[count($listeRetour) -1]) {
                            $listeRetour[] = $objDateFin->format('Y-m-d H:i:s');
                        }
                    }
                    break;
                }
            }
        }
        if ($ordre === 'DESC') {
            rsort($listeRetour);
        } 
        return $listeRetour;
    }
    
}