<?php
    /**
     * Extension pour le match
     *
     * PHP version 5
     *
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class MatchExt extends SinapsModel {

    /**
     * Retourne l'équipe domicile
     */
     public function equipe_dom() {
         $equipe = Equipe::find($this->equipe_id_dom);
         return $equipe;
    }

    /**
     * Retourne l'équipe extérieur
     */
     public function equipe_ext() {
         $equipe = Equipe::find($this->equipe_id_ext);
         return $equipe;
    }
}
