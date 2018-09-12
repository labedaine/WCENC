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
     * La competition sur laquelle on a un pronostic
     * [competition 1-1 pronostic]
     * Le pronostic de sur une competition
     * 
     * @var INTEGER NOT NULL
     */
    protected $competition_id = NULL;

    /**
     * Utilisateur du pronostic
     * [utilisateur 1-1 pronostic]
     * Le pronostic de l'utilisateur
     * 
     * @var INTEGER NOT NULL
     */
    protected $utilisateur_id = NULL;

    /**
     * Equipe du pronostic
     * [equipe 1-1 pronostic]
     * Le pronostic sur l'equipe
     * 
     * @var INTEGER NOT NULL
     */
    protected $equipe_id = NULL;


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

    /**
     * Equipe du pronostic
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
    public function equipe() {
         $relation = $this->belongsTo("Equipe");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
