<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Paris extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * Match parie
     * [match 1-N paris]
     * paris
     * 
     * @var integer NOT NULL
     */
    protected $match_id = NULL;

    /**
     * Utilisateur faisant le paris
     * [utilisateur 1-N paris]
     * paris
     * 
     * @var integer NOT NULL
     */
    protected $utilisateur_id = NULL;

    /**
     * 
     * 
     * @var integer NOT NULL
     */
    protected $score_dom = NULL;

    /**
     * 
     * 
     * @var integer NOT NULL
     */
    protected $score_ext = NULL;


    /*
        Relations *
    */

    /**
     * Match parie
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function match() {
         $relation = $this->belongsTo("Match");
         return $relation;
    }

    /**
     * Utilisateur faisant le paris
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
