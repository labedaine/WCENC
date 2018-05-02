<?php
/**
 * Génération des SinapsModel et du script de création du schéma.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__.'/../../apps/commun/php/utils/StringTools.php';

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
    public $namespace = NULL;

    public function __construct($nom, $namespace=NULL) {
        static::$cheminVersLesFichiersModeles = __DIR__ . "/../models";

        $this->nom = $nom;
        $this->classeMere = "SinapsModel";
        $this->namespace = $namespace;

        static::$index[$nom] = $this;
    }

    public static function findOrCreate($nom, $namespace=NULL) {
        if (array_key_exists($nom, static::$index)) {
            return static::$index[$nom];
        }

        return new Modele($nom, $namespace);
    }

    public function setCommentaire($commentaire) {
        $this->commentaire = $commentaire;

        $matches = array();
        if ( preg_match("/\[Classe: (.+)\]/", $commentaire, $matches)) {
            print "    $this->nom (BDD) devient $matches[1] (PHP)\n";
            $this->nom = $matches[1];
        }
    }

    public function ajouterRelationExterne(Relation $relation) {
        $this->relationsExternes[] = $relation;
    }

    public function genererFichier($nomBDD) {
        if (file_exists(static::$cheminVersLesFichiersModeles . "/$nomBDD/" . $this->nom . "Ext.php")) {
            print "     <<<<<< Extension du modèle\n";
            $this->classeMere = $this->nom . "Ext";
        }

        file_put_contents(
            static::$cheminVersLesFichiersModeles . "/" .
            $nomBDD . "/" .
            $this->nom . ".php", $this->toString()
        );
    }

    public function toString() {
        $reponse = "<?php\n";
        $enteteFichier = "Classe générée automatiquement par gen_classes.\n\n" .
                          $this->commentaire .
                         "\nPHP version 5\n\n" .
                         "@author Génération Automatique <personne.quinexistepas@dgfip.finances.gouv.fr>";
        $reponse .= MysqlWorkbench::reformaterTexte($enteteFichier) . "\n";

        $reponse .= "class $this->nom extends $this->classeMere {";
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

        if ( strpos($type, "DATETIME")) {
            $this->modele->timestamps[] = $this->nom;
        }
    }

    public function setCommentaire($commentaire) {
        if ( preg_match("/_id$/", $this->nom)) {
            // C'est une relation

            $matches = array();
            if ( preg_match( "/^(.*)..\[(\w+) 1\-(.) (\w+)\]..(.*)$/", $commentaire, $matches)) {
                $relation = new Relation();
                $relation->belongsToClass = $this->modele;
                $relation->nomLocal = $matches[2];
                $relation->commentaireLocal = $matches[1];
                $relation->nomDistant = $matches[4];
                $relation->commentaireDistant = $matches[5];
                $relation->cardinaliteDistant = $matches[3];

                $this->modele->relations[$this->nom] = $relation;
                $relation->registerInIndex();
            }

            if ( preg_match( "/^\[Destination: ([^\]]*)\]..(.*)$/", $commentaire, $matches)) {
                $nomClasseDistante = $matches[1];
                $classeDistante = Modele::findOrCreate($nomClasseDistante);
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

    public function registerInIndex() {

    }

    public function toString() {
        $commentaire = $this->commentaireLocal . "\\n".
                       "Utilisation interne au framework uniquement\\n".
                       "@return SinapsRelation";
        $reponse  = MysqlWorkbench::reformaterTexte($commentaire);
        $reponse .= "    public function " . $this->nomLocal . "() {\n";
        $className = $this->classeDistante;
        $reponse .= "         \$relation = \$this->belongsTo(\"" . $className . "\");\n";
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
        $reponse .= "         \$relation = \$this->" . $this->cardinaliteDistantToPHP() . "(\"" . $otherClass .  "\");\n";
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
    /** @var String si l'option est passée, contient le préfix que les classes doivent respecter pour
     * être incluses dans la génération */
    protected $suffixeDesClassesAGenerer = null;
    /** @var String nom de la BDD (restitution ou configuration) */
    protected $nomBDD = "__DO_NOT_EXIST__";

    public function __construct($nomBDD, $suffixeDesClassesAGenerer) {
        $this->nomBDD = $nomBDD;

        $this->mysqlWorkbenchFile = __DIR__."/base.sql";
        $this->sqlOutputFile = __DIR__."/create_base.sql";
        $this->postgresqlOutputFile = __DIR__."/create_base_pg.sql";

        $this->suffixeDesClassesAGenerer = $suffixeDesClassesAGenerer;
    }

    public function go() {
		
        $fileDesc = fopen($this->mysqlWorkbenchFile, "rb");
        $fileOut = fopen($this->sqlOutputFile, "w");
        
        if ( !$fileDesc) {
            print "Impossible d'ouvrir $this->mysqlWorkbenchFile\n";
            exit(1);
        }
        if (!$fileOut) {
            print "Imposible d'ouvrier $this->sqlOutputFile\n";
            exit(1);
        }

        $namespace = NULL;
        if ($this->nomBDD === "configuration") {
            $namespace = "configuration";
        }

        fwrite($fileOut, "ALTER DATABASE CHARACTER SET utf8 COLLATE utf8_bin;\n\n");

        $modeles = array();
        $currentRelation  = NULL;

        while (!feof($fileDesc)) {
            $ligne = fgets($fileDesc, 8192);

            $matches = array();
            if (preg_match("/CREATE\s+TABLE.*`(.*)`/", $ligne, $matches)) {
                $nom = $matches[1];
                $currentObjet = Modele::findOrCreate($nom, $namespace);
                $currentObjet->tableName = $nom;

                $currentRelation = NULL;

                $modeles[$currentObjet->tableName] = $currentObjet;
                
            }

            if ( preg_match("/^(\s+`(.+)`(.*))COMMENT '(.*)'/", $ligne, $matches) ||
                 preg_match("/^(\s+`(.+)`(.*))/", $ligne, $matches)) {
                $nom = $matches[2];

                if ($nom !== 'id') {
                    $attribut = new Attribut($currentObjet);
                    $attribut->nom = $nom;
                    $attribut->setType($matches[3]);
                    if (count($matches) > 4) {
                        $attribut->setCommentaire($matches[4]);
                    }
                }

                $ligne = rtrim($ligne);
                $ending = "\n";
                if ( count($matches) > 4 &&
                     StringTools::endsWith($ligne, ',')) {
                    $ending = "," . $ending;
                }

                $ligne = $matches[1] . $ending;
            }

            if ( preg_match("/FOREIGN KEY \(`(.*)`\s*\)/", $ligne, $matches)) {
                if ( array_key_exists($matches[1], $currentObjet->relations)) {
                    $currentRelation = $currentObjet->relations[$matches[1]];
                } else {
                    print "MISSING RELATION: $matches[1] sur $currentObjet->nom\n";
                    $currentRelation = NULL;
                }
            }

            if ( $currentRelation &&
                 preg_match("/REFERENCES `([^`]*)`/", $ligne, $matches)) {
                $nomClasseDistante = $matches[1];
                $currentRelation->classeDistante = $nomClasseDistante;
                $classeDistante = Modele::findOrCreate($nomClasseDistante, $namespace);

                $classeDistante->ajouterRelationExterne($currentRelation);
            }

            if ( preg_match("/^\s*COMMENT = '(.*) \/\* comment truncated \*\/ (.*)$/", $ligne, $matches) ||
                 preg_match("/^COMMENT = '(.*)$/", $ligne, $matches)) {
                $comment = $matches[1];
                if (count($matches) > 2) {
                    $comment .= $matches[2];
                    $ligne = ";"; // On supprime complément le commentaire car il a de problèmes avec l'utf8
                }

                while( substr($comment, -2) !== ";\n")  { // Tant que la ligne ne finit pas par un ';'
                    $comment .= fgets($fileDesc, 8192);
                }

                if (preg_match("/(PARTITION BY .*;)/m", $comment, $matches)) {
                    $ligne = $matches[1];
                }

                $currentObjet->setCommentaire($comment);
            }

            fwrite($fileOut, $ligne);
        }

        // Suppression des fichiers précédement générés
        if ( $this->suffixeDesClassesAGenerer === NULL) {
            $chemin = "../models/". $this->nomBDD;
            print "Suppression de tous les classes générées dans $chemin\n";
            system("find $chemin -name \"*.php\" | grep -v Ext.php | xargs rm");
        }

        // Génération des nouveaux fichiers de mapping
        ksort($modeles);
        
        foreach ($modeles as $nomClasse => $modele) {
            if ( $this->suffixeDesClassesAGenerer === NULL ||
                 StringTools::startsWith($nomClasse, $this->suffixeDesClassesAGenerer)) {
                print "Génération de $modele->nom\n";
                $modele->genererFichier($this->nomBDD);
            }
        }
    }
    
    /**
     * Cré le fichier POSTGRESQL
     */
    
    public function goPg() {
		
        $fileDesc = fopen($this->mysqlWorkbenchFile, "rb");
        $fileOut = fopen($this->postgresqlOutputFile, "w");
        
        if ( !$fileDesc) {
            print "Impossible d'ouvrir $this->mysqlWorkbenchFile\n";
            exit(1);
        }
        if (!$fileOut) {
            print "Imposible d'ouvrier $this->sqlOutputFile\n";
            exit(1);
        }
        
        $mysqlVar = array(
			'/DEFAULT ""/',
			'/DEFAULT "0"/',
			'/DEFAULT "1"/',
            "/ DEFAULT (false|FALSE)/",
            "/ DEFAULT (true|TRUE)/",
            "/CHARACTER SET.*/",	
            "/ (BIG)?INT.*(NOT)? NULL AUTO_INCREMENT/",
            "/ INT(.*)/",
            "/ TINYINT\(\d+\)/",
            "/ TINYINT/",
            "/ MEDIUMINT/",
            "/ FLOAT/",
            "/ DOUBLE/",
            "/ (TINY|MEDIUM|LONG)?TEXT/", 
            "/ DATETIME/",     
            "/(ENGINE =|COMMENT) .*/"
            );
            
            
            $pgVar = array(
            "DEFAULT ''",
            "DEFAULT 0",
            "DEFAULT 1",
            " DEFAULT 0",
            " DEFAULT 1",
            "",	
            " SERIAL",
            " INTEGER",  
            " SMALLINT",
            " SMALLINT",
            " INTEGER",
            " REAL",
            " DOUBLE PRECISION",
            " TEXT",
            " TIMESTAMP",
            ""
            );
        

        $namespace = NULL;

        $modeles = array();
        $buffer = array("AVANT" => array(),
			"APRES"	=> array());

        $debutChamp = FALSE;
        $debutConstraint = FALSE;
        $clefEtrangere = NULL;
        $table = NULL;

        while (!feof($fileDesc)) {
            $ligne = fgets($fileDesc, 8192);
            $matches = array();

			if (preg_match("/CREATE\s+TABLE.*`(.*)`/", $ligne, $matches) ||
			    preg_match("/SET SQL_MODE=@OLD_SQL_MODE/", $ligne, $matches) ) {
				// On vide la table d'avant dans le fichier
				if($table) {
					if(isset($modeles[$table])) {
						
						fwrite($fileOut, join($buffer["AVANT"]));
						
						// On switch les buffers
						$buffer["AVANT"] = $buffer["APRES"];
						$buffer["APRES"] = array();
						
						// UNE ligne de CREATE obligatoirement
						fwrite($fileOut, $modeles[$table]["CREATE"]);
						
						// DES lignes de CHAMP obligatoirement
						foreach($modeles[$table]["CHAMP"] as $id => $contenu) {
							fwrite($fileOut, $contenu . ",\n");
						}
						
						// UNE ligne de PRIMARY KEY obligatoirement
						fwrite($fileOut, $modeles[$table]["PRIMARY"] );
						
						// DES lignes de CONSTRAINT PAS obligatoirement
						if(	!empty($modeles[$table]["CONSTRAINT"])) {
							fwrite($fileOut, ",");
						}

						foreach($modeles[$table]["CONSTRAINT"] as $id => $contenu) {
							fwrite($fileOut, $contenu);
						}
						
						/*
						if(isset($modeles[$table]["PARTITION"])) {
							//fwrite($fileOut, ")\n");
							//fwrite($fileOut, $modeles[$table]["PARTITION"]);
							//fwrite($fileOut, ") ");
						} else {*/
						fwrite($fileOut, ");");
					}
						
					fwrite($fileOut, "\n");

					
					
					
					
					// DES lignes de création d'INDEX PAS obligatoirement
					foreach($modeles[$table]["INDEX"] as $index) {
						fwrite($fileOut, $index);
					}
				}
			}
				
			// Si on est dans le cas de CREATE TABLE
			if(isset($matches[1])) {
				$debutChamp = TRUE;
				$table = $matches[1];

				$modeles[$table] = array(	"CREATE" => array(),
											"CHAMP"  => array(),
											"INDEX"  => array(),
											"CONSTRAINT" => array()
											);
											
				$ligne = preg_replace('/[\`\']/', '"', $ligne);
				$modeles[$table]["CREATE"] = $ligne;
                continue;
            }
            
            // Les anciennes consignes MySQL on les saute
			if(preg_match('/SET.*@/', $ligne)) {
				continue;
			}
              
            $ligne = preg_replace('/[\`\']/', '"', $ligne);
            $ligne = preg_replace($mysqlVar, $pgVar, $ligne);
            $ligne = str_replace(',', '', $ligne);

			if(preg_match("/(\s+PRIMARY KEY \(.*?\))\s+/", $ligne, $matches)) {
				$debutChamp = FALSE;
				// on doit gérer les clef multiples
				$clefs = trim($matches[1]);
				$clefs = preg_replace('/\" \"/', '", "', $clefs);
                                if(substr($clefs, -2) == '))') {
                                    $clefs = substr($clefs,0 ,-1);
                                }
				$modeles[$table]["PRIMARY"] = $clefs ."\n";

				continue;
			}
            
            // Gestion des INDEX
            if ( preg_match('/\s+INDEX (.*) \((.*?)\)/', $ligne, $matches)) {
				/**
				 * UNIQUE INDEX `nom_UNIQUE` (`nom` ASC) )
				 * =>
				 * CREATE INDEX nom_UNIQUE ON MacroDomaine (nom ASC);
				 */
				$nomIndex = $matches[1];
				$indexSurQuoi = array();
				$tampon = "";
				
				$tabIndex = preg_split('/ (ASC|DESC)/', $matches[2], -1,  PREG_SPLIT_DELIM_CAPTURE);

				$count = count($tabIndex);
				$tempon = "";
				for($i=0;$i<$count;$i++) {
					/**
					 * Le tableau est formé comme suit:
						 * array(11) {
						  [0]=>
						  string(15) ""nomEquipement""
						  [1]=>
						  string(3) "ASC"
						  [2]=>
						  string(20) " "nomModeleCollecte""
						  [3]=>
						  string(3) "ASC"
						  [4]=>
						  string(14) " "nomInstance""
						  [5]=>
						  string(3) "ASC"
						  [6]=>
						  string(24) ""
						}
				  */
					$tampon .= " " . $tabIndex[$i];
					if($i%2 === 1) {
						$indexSurQuoi[] = $tampon;
						$tampon = "";
					}
				}
				$indexSurQuoi = join(', ', $indexSurQuoi );
				
					
				if(!preg_match('/'.$table.'/',$nomIndex)) {
					$nomIndex = preg_replace('/"$/', '_'.$table . '"', $nomIndex);
				}
				
				// Pour être sur du nom unique on met un grain de sable
				$modeles[$table]["INDEX"][] = sprintf("CREATE INDEX %s ON \"%s\" (%s NULLS LAST);\n",
												$nomIndex,
												$table,
												$indexSurQuoi
											);
				continue;
			}
            
            // Gestion des contraintes
				// fin des contraintes
			if ( !preg_match('/(CONSTRAINT|FOREIGN|REFERENCES|ON DEL|ON UP)/', $ligne, $matches)) {
				$debutConstraint = FALSE;
				$clefEtrangere = NULL;
			}
			
            if ( preg_match('/CONSTRAINT/', $ligne, $matches)) {
				// Si on retrouve CONSTRAINT avec $debutConstraint = TRUE
				// C'est qu'on doit ajouter une virgule
				if($debutConstraint) {
					$modeles[$table]["CONSTRAINT"][] = ",";
				}
				
				$debutConstraint = TRUE;
				//$modeles["NO_ID"] = "," . $ligne;
				$modeles[$table]["CONSTRAINT"][] = $ligne;
				continue;
			}
			
			if($debutConstraint) {
				
				if(!preg_match('/\(.*\)/', $ligne)) {
					$ligne = str_replace(')', '', $ligne);
				}
				$modeles[$table]["CONSTRAINT"][] = $ligne;
				
				if ( preg_match('/FOREIGN KEY \((.*)\)/', $ligne, $matches)) {
					
					$clefEtrangere = trim($matches[1]);
					//$modeles[$table]["CHAMP"][$clefEtrangere] .= $modeles["NO_ID"];
					//$modeles[$table]["CHAMP"][$clefEtrangere] .= $ligne;
					$modeles["NO_ID"] = "";
				} else {
					// clefEtrangere doit etre créé
					if(!preg_match('/\(.*\)/', $ligne)) {
						$ligne = str_replace(')', '', $ligne);
					}
					//$modeles[$table]["CHAMP"][$clefEtrangere] .= $ligne;
				}
				continue;
			}

			// On gère les PARTITIONS
			if ( preg_match("/(PARTITION BY HASH.*)/", $ligne, $matches)) {
				$modeles[$table]["PARTITION"] = $matches[1];
			}

            // On gère les commentaires
			if ( preg_match("/^(\s+(\"[\w]*\") .*)/", $ligne, $matches)) {
                $nom = $matches[2];

                $ligne = rtrim($ligne);
                $ligne = $matches[1];
                $modeles[$table]["CHAMP"][$nom] = $ligne;
                continue;
            }

            // On ajoute le CASCADE quand on DROP sinon la FK gueule
            if(!empty($ligne)) {
				if(preg_match('/DROP TABLE/', $ligne)) {
					$ligne = str_replace(';', ' CASCADE;', $ligne);
				
					// Sinon on met tout dans le buffer
					if(!$table) {
						$buffer["AVANT"][] = $ligne;
					} else {
						$buffer["APRES"][] = $ligne;
					}
				}
			}
        }
        // On modifie toutes les doubles quotes en rien
        //file_put_contents( $this->postgresqlOutputFile,
        //                   preg_replace('/"/', '' , file_get_contents($this->postgresqlOutputFile)));
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
    print "\tGénère les fichiers modèles dans ../models\n";
    print "\tGénère le fichier create_base.sql corrigeant les erreurs de génération dans le répertoire de lancement du gen_classes\n";
    exit(1);
}

/*******************************
 * MAIN
 *******************************/


$parser = new MysqlWorkbench($argv[1], count($argv)===3 ? $argv[2] : null);
$parser->go();
$parser->goPg();

