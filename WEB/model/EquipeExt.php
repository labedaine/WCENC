<?php
    /**
     * Extension pour l'équipe
     *
     * PHP version 5
     *
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class EquipeExt extends SinapsModel {

    /**
     * Retourne l'ensemble des matchs
     */
     public function matchs() {
         $matchsDomicile = $this->matchsDomicile();
         $matchsVisiteur = $this->matchsVisiteur();
         $matchs = array_merge($matchsDomicile, $matchsVisiteur);
         return $matchs;
    }
}
