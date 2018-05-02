<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Match extends SinapsModel {
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
     * Équipe 1 jouant le match
     * [equipe1 1-N matchsDomicile]
     * matchs
     * 
     * @var varchar(3)
     */
    protected $code_equipe_1 = NULL;

    /**
     * Équipe 2 jouant le match
     * [equipe2 1-N matchsVisiteur]
     * matchs
     * 
     * @var varchar(3)
     */
    protected $code_equipe_2 = NULL;

    /**
     * État du match
     * [etat 1-N match]
     * match
     * 
     * @var varchar(3) DEFAULT 'AVE'::bpchar NOT NULL
     */
    protected $code_etat_match = NULL;

    /**
     * Le stade où se déroule le match
     * [stade 1-N match]
     * match
     * 
     * @var integer NOT NULL
     */
    protected $stade_id = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT 0
     */
    protected $score_equipe_1 = NULL;

    /**
     * 
     * 
     * @var integer DEFAULT 0
     */
    protected $score_equipe_2 = NULL;

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
     * Équipe 1 jouant le match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function equipe1() {
         $relation = $this->belongsTo("Equipe");
         return $relation;
    }

    /**
     * Équipe 2 jouant le match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function equipe2() {
         $relation = $this->belongsTo("Equipe");
         return $relation;
    }

    /**
     * État du match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function etat() {
         $relation = $this->belongsTo("Etat_match");
         return $relation;
    }

    /**
     * Le stade où se déroule le match
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function stade() {
         $relation = $this->belongsTo("Stade");
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

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
