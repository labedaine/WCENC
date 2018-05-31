<?php
/**
 * Génération des SinapsModel et du script de création du schéma.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

//require_once __DIR__.'/../../ressource/php/utils/StringTools.php';

class Modele {
    protected static $index = array();
    protected static $cheminVersLesFichiersModeles;
    public $nom;
    public $tableName;
    public $commentaire;
    public $attributsDeTypeTimeStamp = array();
    public $relations = array();
    public $relationsExternes = array();
    public $attributs = array();
    public $timestamps = array();

    public function __construct($nom) {
        static::$cheminVersLesFichiersModeles = __DIR__ . "/../WEB/model/";

        $this->nom = $nom;
        $this->classeMere = "SinapsModel";

        static::$index[$nom] = $this;
    }

    public static function findOrCreate($nom) {
        if (array_key_exists($nom, static::$index)) {
            return static::$index[$nom];
        }

        return new Modele($nom);
    }

    public function setCommentaire($commentaire) {
        $this->commentaire = $commentaire;
    }

    public function ajouterRelationExterne(Relation $relation) {
        $this->relationsExternes[] = $relation;
    }

    public function genererFichier() {
        if (file_exists(static::$cheminVersLesFichiersModeles . "/" . ucfirst($this->nom) . "Ext.php")) {
            print "     <<<<<< Extension du modèle\n";
            $this->classeMere = ucfirst($this->nom) . "Ext";
        }

        file_put_contents(
            static::$cheminVersLesFichiersModeles . "/" .
            ucfirst($this->nom) . ".php", $this->toString()
        );
    }

    public function toString() {
        $reponse = "<?php\n";
        $enteteFichier = "Classe générée automatiquement par gen_classes.\n\n" .
                          $this->commentaire .
                         "\nPHP version 5\n\n" .
                         "@author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>";
        $reponse .= MysqlWorkbench::reformaterTexte($enteteFichier) . "\n";

        $reponse .= "class ".ucfirst($this->nom)." extends $this->classeMere {";
        if ($this->tableName !== $this->nom) {
            $reponse .= "\n    static public \$table = '$this->tableName';\n";
        }

        if (count($this->timestamps) > 0) {
            $toWrite = array();
            foreach( $this->timestamps as $key => $colonne) {
                $toWrite[$key] = '"' . $colonne . '" => "timestamp"';
            }

            $reponse .= "\n    static public \$formats = array(" .
                        implode(',', $toWrite) . ");";
        }

        $reponse .= "\n" .
                    "    /*\n".
                    "        Attributs *\n".
                    "    */\n\n";
        foreach( $this->attributs as $attribut) {
            $reponse .= $attribut->toString();
        }

        $reponse .= "\n" .
                    "    /*\n".
                    "        Relations *\n".
                    "    */\n\n";
        foreach($this->relations as $nomAttribut => $relation) {
            $reponse .= $relation->toString();
        }

        foreach ($this->relationsExternes as $relation) {
            $reponse .= $relation->toStringPointDeVueDestination();
        }

        $reponse .= "    public function __construct(array \$values=NULL) {\n";
        $reponse .= "        parent::__construct(\$values);\n";
        $reponse .= "    }\n\n";

        $reponse .= "}\n";

        return $reponse;
    }
}

/**
 * Classe reprensentant un attribut du modele
 */
class Attribut {
    public $modele;
    public $nom;
    public $commentaire = null;
    protected $type;

    public function __construct( Modele $modele) {
        $this->modele = $modele;
        $modele->attributs[] = $this;
    }

    public function setType($type) {
        $this->type = $type;

        if ( strpos(strtoupper($type), "TIMESTAMP") !== false) {
            $this->modele->timestamps[] = $this->nom;
        }
    }

    public function setCommentaire($commentaire) {
        if ( preg_match("/_id$/", $this->nom) || preg_match("/^code_/", $this->nom)) {
            // C'est une relation

            $matches = array();

            // Si le commentaire finit par > on supprime ce motif
            if(preg_match("/>$/", $commentaire)) {
                $commentaire = substr($commentaire,0,-1);
            }

            // Utilisateur a qui appartient la session [utilisateur 1-1 session] La session http du user >
            if ( preg_match( "/^(.*).\[(\w+) 1\-(.) (\w+)\].(.*).+$/", $commentaire, $matches)) {
                $relation = new Relation();
                $relation->belongsToClass = $this->modele;
                $relation->nomLocal = $matches[2];
                $relation->commentaireLocal = $matches[1];
                $relation->nomDistant = $matches[4];
                $relation->commentaireDistant = $matches[5];
                $relation->cardinaliteDistant = $matches[3];

                $this->modele->relations[$this->nom] = $relation;

                // On remplace les [ et ] par des retours chariot
                $commentaire = str_replace(" [", "\n[", $commentaire);
                $commentaire = str_replace("] ", "]\n", $commentaire);
            }

            if ( preg_match( "/\[Destination: ([^\]]*)\]..(.*)$/", $commentaire, $matches)) {
                $nomClasseDistante = ucfirst($matches[1]);
                $classeDistante = Modele::findOrCreate($nomClasseDistante);
                $relation = new Relation();
                $relation->classeDistante = $classeDistante->nom;

                $classeDistante->ajouterRelationExterne($relation);

                $commentaire = $matches[2];
            }
        }

        $this->commentaire = $commentaire;
    }

    public function toString() {
        $commentaire = $this->commentaire .
                        "\\n\\n@var " . trim(str_replace(',', '', $this->type));
        $reponse  = MysqlWorkbench::reformaterTexte($commentaire);
        $reponse .= "    protected \$$this->nom = NULL;\n\n";

        return $reponse;
    }
}

/**
 * Classe représentant une relation entre 2 tables
 */
class Relation {
    static $index = array();
    public $belongsToClass;
    public $nomLocal;
    public $nomDistant;
    public $cardinaliteDistant;

    public function toString() {
        $commentaire = $this->commentaireLocal . "\\n".
                       "Utilisation interne au framework uniquement\\n".
                       "@return SinapsRelation";
        $reponse  = MysqlWorkbench::reformaterTexte($commentaire);
        $reponse .= "    public function " . $this->nomLocal . "() {\n";
        $className = $this->classeDistante;
        $reponse .= "         \$relation = \$this->belongsTo(\"" . ucfirst($className) . "\");\n";
        $reponse .= "         return \$relation;\n";
        $reponse .= "    }\n\n";

        return $reponse;
    }

    public function toStringPointDeVueDestination() {
        $commentaire = $this->commentaireDistant .
                       "\\nUtilisation interne au framework uniquement" .
                       "\\n@return SinapsRelation";

        $reponse  = MysqlWorkbench::reformaterTexte($commentaire);
        $reponse .= "     public function " . $this->nomDistant . "() {\n";
        $otherClass = $this->belongsToClass->nom;
        $reponse .= "         \$relation = \$this->" . $this->cardinaliteDistantToPHP() . "(\"" . ucfirst($otherClass) .  "\");\n";
        $reponse .= "         return \$relation;\n";
        $reponse .= "    }\n\n";

        return $reponse;
    }

    public function cardinaliteDistantToPHP() {
        switch( $this->cardinaliteDistant) {
            case '*':
            case 'N':
                return "hasMany";
            break;

            case '1':
                return "hasOne";
            break;

            default:
                return "ERROR!";
            break;
        }
    }
}

/**
 * Classe principale chargée de la génération
 *
 * Parcourt le fichier générer par Mysql Workbench
 * Construit un arbre mémoire des classes/tables et de leurs attributs et relation
 * Génère le fichier .sql de création du schéma en coupant les commentaires
 * Génère les fichiers .php utilisant l'ORM
 *
 */
class MysqlWorkbench {
    /** @var String chemin complet vers le fichier mysqlworkbench */
    protected $mysqlWorkbenchFile;
    /** @var String chemin complet vers le fichier sql de création de la structure en BDD */
    protected $sqlOutputFile;
    /** @var String chemin complet vers le fichier postgresql de création de la structure en BDD */
    protected $postgresqlOutputFile;

    public function __construct() {
        $this->mysqlWorkbenchFile = __DIR__."/base.sql";
        $this->sqlOutputFile = __DIR__."/create_base.sql";
        $this->postgresqlOutputFile = __DIR__."/create_base_pg.sql";

    }

    public function go() {

        $fileDesc = fopen($this->mysqlWorkbenchFile, "rb");

        if ( !$fileDesc) {
            print "Impossible d'ouvrir $this->mysqlWorkbenchFile\n";
            exit(1);
        }

        $modeles = array();
        $currentRelation  = NULL;

        while (!feof($fileDesc)) {
            $ligne = fgets($fileDesc, 8192);
            $matches = array();

            if(preg_match("/(SEQUENCE|START|INCREMENT|MINVALUE|MAXVALUE|CACHE)/", $ligne)) {
                continue;
            }

            if (preg_match("/CREATE\s+TABLE\s+([\w]+)\s+\(/i", $ligne, $matches)) {
                $nom = $matches[1];
                $currentObjet = Modele::findOrCreate($nom);
                $currentObjet->tableName = $nom;

                $currentRelation = NULL;

                $modeles[$currentObjet->tableName] = $currentObjet;
            }

            //~ if (preg_match("/PRIMARY\s+KEY\s+([\w]+)/", $ligne, $matches)) {
                //~ $nom = $matches[1];
                //~ $attribut = new Attribut($currentObjet);
                //~ $attribut->nom = $nom;
                //~ $attribut->setType("PRIMARY KEY");
            //~ }

            // On cherche les attributs
            if ( preg_match("/^(\s+(\w+) (.*)),\s*(--\s+(.*))/", $ligne, $matches) ||
                 preg_match("/^(\s+(\w+) (.*)),/", $ligne, $matches)) {
                $nom = $matches[2];

                if ($nom !== 'id') {
                    $attribut = new Attribut($currentObjet);
                    $attribut->nom = $nom;
                    $attribut->setType($matches[3]);
                    if (count($matches) > 4) {
                        $comment = $matches[5];
                        $attribut->setCommentaire($comment);

                        while( substr($comment, -1) === ">")  { // Tant que la ligne finit par un >
                            $comment = fgets($fileDesc, 8192);
                            $attribut->setCommentaire($comment);
                        }
                    }
                }

                /*$ligne = rtrim($ligne);
                $ending = "\n";
                if ( count($matches) > 4 &&
                     StringTools::endsWith($ligne, ',')) {
                    $ending = "," . $ending;
                }

                $ligne = $matches[1] . $ending;*/
            }

            // CONSTRAINT fk_match_equipe1 FOREIGN KEY (code_equipe_1) REFERENCES equipe(code_equipe)
            if ( preg_match("/FOREIGN KEY\s+\((\w+)\s*\)\s+REFERENCES/", $ligne, $matches)) {
                if ( array_key_exists($matches[1], $currentObjet->relations)) {
                    $currentRelation = $currentObjet->relations[$matches[1]];
                } else {
                    print "MISSING RELATION: $matches[1] sur $currentObjet->nom\n";
                    $currentRelation = NULL;
                }
            }

            if ( $currentRelation &&
                 preg_match("/REFERENCES\s+(\w+)\s+/", $ligne, $matches)) {
                $nomClasseDistante = $matches[1];
                $currentRelation->classeDistante = $nomClasseDistante;
                $classeDistante = Modele::findOrCreate($nomClasseDistante);

                $classeDistante->ajouterRelationExterne($currentRelation);
            }

            // Le commentaire sur la table est toujours à la suite de la déclaration de la table
            if ( preg_match("/^COMMENT ON TABLE (\w+) IS '(.*)/", $ligne, $matches)) {
                $nom = $matches[1];
                $comment = $matches[2];

                $currentObjet = Modele::findOrCreate($nom);

                while( substr($comment, -3) !== "';\n")  { // Tant que la ligne ne finit pas par un ';'
                    $comment .= fgets($fileDesc, 8192);
                }

                $currentObjet->setCommentaire($comment);
            }
        }

        // Suppression des fichiers précédement générés
        $chemin = "../WEB/model/";
        print "Suppression de tous les classes générées dans $chemin\n";
        system("find $chemin -name \"*.php\" | grep -v Ext.php | xargs rm");


        // Génération des nouveaux fichiers de mapping
        ksort($modeles);

        foreach ($modeles as $nomClasse => $modele) {
            print "Génération de " . ucFirst($modele->nom) . "\n";
            $modele->genererFichier();
        }
    }

    /**
     * Sort à l'écran le code d'une liste de classes
     * @param array<Modele> $modeles liste des modèles à afficher
     */
    public function printAll(array $modeles) {
        foreach( $modeles as $modele) {
            print $modele->toString();
        }
    }

    /**
     * Prend un chaine de commentaire telle que générée par le workbench et la remet d'aplomb.
     *
     * Détails:
     * \' ==> '
     * \n ==> vrai retour chariot
     * Vire /* et *\/
     *
     * Ajout des balise de commentaire PHP
     *
     * @param string $texte            le texte à reformater
     * @param string $avantCommentaire le texte à mettre en tete de commentaire
     * @param string $chaqueLigne      le texte à ajouter aux lignes intermédiaire
     * @param string $apresCommentaire le texte de fin de commentaire
     * @return string                   le texte converti
     */
    static public function reformaterTexte(
        $texte,
        $avantCommentaire='    /**',
        $chaqueLigne="     *",
        $apresCommentaire='     */'
    ) {
        $texte = str_replace("\\'", "'", $texte);
        $texte = str_replace('/*', "", $texte);
        $texte = str_replace('*/', "", $texte);
        $lignes = explode("\\n", $texte);

        $reponse = $avantCommentaire;
        foreach( $lignes as $ligne) {
            foreach( explode("\n", $ligne) as $ligneUnique)
            $reponse .= "\n$chaqueLigne $ligneUnique";
        }

        $reponse .= "\n" . $apresCommentaire . "\n";

        return $reponse;
    }
}

/**
 * Décrit les conditions d'appel du script
 *
 * @param  Array $argv les arguments passés au script
 */
function usage($argv) {
    print "\tA partir d'un fichier SQL généré par mysqlWorkbench génère les classes PHP\n";

    print "\tLit le fichier ./base.sql\n";
    print "\tGénère les fichiers modèles dans ../model\n";
    print "\tGénère le fichier create_base.sql corrigeant les erreurs de génération dans le répertoire de lancement du gen_classes\n";
    exit(1);
}

/*******************************
 * MAIN
 *******************************/


$parser = new MysqlWorkbench();
$parser->go();

