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

    /**
     * Tableau de correspondance des équipes
     */

    static public $correspondancesEquipe = array(
         "Russia" => "Russie",
         "Saudi Arabia" => "Arabie Saoudite",
         "Egypt" => "Egypte",
         "Morocco" => "Maroc",
         "Spain" => "Espagne",
         "Australia" => "Australie",
         "Peru" => "Pérou",
         "Denmark" => "Danemark",
         "Argentina" => "Argentine",
         "Iceland" => "Islande",
         "Croatia" => "Croatie",
         "Serbia" => "Serbie",
         "Brazil" => "Brésil",
         "Switzerland" => "Suisse",
         "Germany" => "Allemagne",
         "Mexico" => "Mexique",
         "Sweden" => "Suède",
         "Korea Republic" => "Corée du sud",
         "Belgium" => "Belgique",
         "Tunisia" => "Tunisie",
         "England" => "Angleterre",
         "Poland" => "Pologne",
         "Senegal" => "Senegal",
         "Colombia" => "Colombie",
         "Japan" => "Japon"
    );
}
