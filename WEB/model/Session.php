<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * Objet gérant la session utilisateur en lien avec le cookie token';
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Session extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var VARCHAR(45) NOT NULL
     */
    protected $token = NULL;

    /**
     * 
     * 
     * @var TIMESTAMP NOT NULL
     */
    protected $date = NULL;

    /**
     * Utilisateur a qui appartient la session
     * [utilisateur 1-1 session]
     * La session http du user
     * 
     * @var INTEGER
     */
    protected $utilisateur_id = NULL;


    /*
        Relations *
    */

    /**
     * Utilisateur a qui appartient la session
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
