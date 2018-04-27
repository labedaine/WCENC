<?php
    /**
     * Classe générée automatiquement par gen_classes.
     * 
     * 
     * PHP version 5
     * 
     * @author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>
     */

class Groupe extends GroupeExt {
    /*
        Attributs *
    */

    /**
     * Nom du groupe d'utilisateurs
     * 
     * @var VARCHAR(255) NOT NULL
     */
    protected $nom = NULL;

    /**
     * adresse courrielle du
     * groupe d'utilisateurs
     * 
     * @var VARCHAR(255) NULL
     */
    protected $groupeMail = NULL;

    /**
     * numero de telephone
     * du groupe d'utilisateurs
     * 
     * @var VARCHAR(45) NULL
     */
    protected $groupeTelephone = NULL;

    /**
     * description 
     * du groupe d'utilisateurs
     * 
     * @var VARCHAR(255) NULL
     */
    protected $groupeDescription = NULL;

    /**
     * nom du groupe dans ServiceManager
     * 
     * @var VARCHAR(255) NULL
     */
    protected $nomSMA = NULL;


    /*
        Relations *
    */

    /**
     * Les tableaux de bord configurés pour ce groupe
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function tableauxDeBord() {
         $relation = $this->hasMany("TableauDeBord");
         return $relation;
    }

    /**
     * Les utilisateurs.
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function utilisateurs() {
         $relation = $this->hasMany("UtilisateurDuGroupe");
         return $relation;
    }

    /**
     * Les applications
     * Utilisation interne au framework uniquement
     * @return SinapsRelation
     */
     public function applications() {
         $relation = $this->hasMany("ApplicationDuGroupe");
         return $relation;
    }

    public function __construct(array $values=NULL) {
        parent::__construct($values);
    }

}
