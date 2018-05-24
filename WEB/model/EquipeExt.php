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
     * Retourne l'ensemble des matchs pour une équipe
     */
     public function matchs() {
         $matchsDomicile    = Match::where('equipe_id_dom', $this->id)->get();
         $matchsExterieur   = Match::where('equipe_id_ext', $this->id)->get();
         $matchs = array_merge($matchsDomicile, $matchsExterieur);
         return $matchs;
    }
}
