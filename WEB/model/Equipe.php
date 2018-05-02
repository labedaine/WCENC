<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Equipe extends EquipeExt {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var varchar(3) NOT NULL
     */
    protected $code_equipe = NULL;

    /**
     * 
     * 
     * @var varchar(50) NOT NULL
     */
    protected $pays = NULL;

    /**
     * 
     * 
     * @var varchar(1)
     */
    protected $code_groupe = NULL;


    /*
        Relations *
    */

    /**
     * match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function matchsDomicile() {
         $relation = $this->hasMany("Match");
         return $relation;
    }

    /**
     * match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function matchsVisiteur() {
         $relation = $this->hasMany("Match");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
