<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Competition extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $libelle = NULL;


    /*
        Relations *
    */

    /**
     * Le pronostic de sur une competitio
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function pronostic() {
         $relation = $this->hasOne("Pronostic");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
