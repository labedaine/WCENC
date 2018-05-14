<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Utilisateur extends SinapsModel {
    /*
        Attributs *
    */

    /**
     * 
     * 
     * @var VARCHAR(255) NULL DEFAULT NULL
     */
    protected $nom = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NULL DEFAULT NULL
     */
    protected $prenom = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $login = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $email = NULL;

    /**
     * 
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $password = NULL;

    /**
     * 
     * 
     * @var SMALLINT
     */
    protected $isactif = NULL;

    /**
     * 
     * 
     * @var SMALLINT
     */
    protected $isadmin = NULL;


    /*
        Relations *
    */

    /**
     * La session http du use
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function session() {
         $relation = $this->hasOne("Session");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
