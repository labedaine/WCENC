<?php
    /**
     * Classe générée automatiquement par gen_classes.
     *
     *
     * PHP version 5
     *
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Utilisateur extends UtilisateurExt {
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
     * @var SMALLINT NOT NULL DEFAULT 0
     */
    protected $promotion = NULL;

    /**
     *
     *
     * @var SMALLINT NOT NULL DEFAULT 0
     */
    protected $isactif = NULL;

    /**
     *
     *
     * @var SMALLINT NOT NULL DEFAULT 0
     */
    protected $isadmin = NULL;

    /**
     *
     *
     * @var integer NOT NULL DEFAULT 0
     */
    protected $points = NULL;


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
