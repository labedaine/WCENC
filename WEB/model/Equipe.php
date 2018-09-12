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
     * Le pronostic sur l'equip
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
