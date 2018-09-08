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

    /**
     * 
     * 
     * @var TEXT
     */
    protected $lien_image = NULL;

    /*
        Relations *
    */

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
