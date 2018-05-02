<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Etat_match extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var varchar(3) NOT NULL
     */
    protected $code_etat_match = NULL;

    /**
     * 
     * 
     * @var varchar(10) NOT NULL
     */
    protected $libelle = NULL;


    /*
        Relations *
    */

    /**
     * matc
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function match() {
         $relation = $this->hasMany("Match");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
