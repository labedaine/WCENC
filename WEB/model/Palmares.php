<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Palmares extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var SMALLINT NOT NULL DEFAULT 0
     */
    protected $rang = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NULL DEFAULT NULL
     */
    protected $competition = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NULL DEFAULT NULL
     */
    protected $saison = NULL;

    /**
     * 
     * 
     * @var integer NOT NULL DEFAULT 0
     */
    protected $points = NULL;

    /**
     * Utilisateur du palmares
     * [utilisateur 1-1 palmares]
     * Le palmares de l'utilisateur
     * 
     * @var INTEGER
     */
    protected $utilisateur_id = NULL;


    /*
        Relations *
    */

    /**
     * Utilisateur du palmares
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function utilisateur() {
         $relation = $this->belongsTo("Utilisateur");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
