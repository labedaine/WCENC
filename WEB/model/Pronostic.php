<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Pronostic extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $libelle = NULL;

    /**
     * La competition sur laquelle on a un pronostic
     * [competition 1-1 pronostic]
     * Le pronostic de sur une competition
     * 
     * @var INTEGER
     */
    protected $competition_id = NULL;

    /**
     * Utilisateur du pronostic
     * [utilisateur 1-1 pronostic]
     * Le pronostic de l'utilisateur
     * 
     * @var INTEGER
     */
    protected $utilisateur_id = NULL;


    /*
        Relations *
    */

    /**
     * La competition sur laquelle on a un pronostic
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function competition() {
         $relation = $this->belongsTo("Competition");
         return $relation;
    }

    /**
     * Utilisateur du pronostic
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
