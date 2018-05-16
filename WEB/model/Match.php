<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Match extends MatchExt {
    static public $formats = array("date_match" => "timestamp");
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var timestamp with time zone NOT NULL
     */
    protected $date_match = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT NULL
     */
    protected $equipe_id_dom = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT NULL
     */
    protected $equipe_id_ext = NULL;

    /**
     * État du match
     * [etat 1-N match]
     * match
     * 
     * @var integer NOT NULL
     */
    protected $etat_id = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT NULL
     */
    protected $score_dom = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT NULL
     */
    protected $score_ext = NULL;

    /**
     * La phase du match
     * [phase 1-N match]
     * match
     * 
     * @var integer NOT NULL
     */
    protected $phase_id = NULL;


    /*
        Relations *
    */

    /**
     * État du match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function etat() {
         $relation = $this->belongsTo("Etat");
         return $relation;
    }

    /**
     * La phase du match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function phase() {
         $relation = $this->belongsTo("Phase");
         return $relation;
    }

    /**
     * pari
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function paris() {
         $relation = $this->hasMany("Paris");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
