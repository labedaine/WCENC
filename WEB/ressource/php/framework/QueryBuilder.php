<?php
/**
 * Classe chargée d'orchestrer la eager fetching.
 *
 * Ne doit pas être construire directement mais via @see SinapsModel#with
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class QueryBuilder {
    /**
     * Classe à charger initialement.
     *
     * @var string
     */
    protected $baseModel;
    /**
     * Les noms des relations à charger.
     *
     * @var array<String>
     */
    protected $relationNames;
    /**
     * Stockage des résultats des requetes intermédiaires.
     *
     * @var array< relationName => array<SinapsModel>>
     */
    protected $subRequestIndex = array();
    /**
     * Les relations déjà chargées.
     *
     * @var array<String>
     */
    protected $loadedRelations = array();
    /**
     * Liste des conditions where applicables.
     *
     * @var array<String>
     */
    protected $wheres = array();
    /**
     * Ensemble des clauses ORDER.
     *
     * @var array<string>
     */
    protected $orderClauses = array();
    /**
     * Constructeur
     *
     * @param String $baseModel: la classe de base du chargement
     */
    public function __construct($baseModel) {
        $this->baseModel = $baseModel;
    }

    /**
     * Ajoute une relation à la liste des relations à charger
     *
     * @param string $relationName le nom de public de la relation
     */
    public function addRelation($relationName) {
        $this->relationNames[] = $relationName;
    }

    /**
     * Charge un objet et toutes les relations liées
     *
     * @TODO: FIXME ca doit pas marcher ca
     * @param int $id l'id de l'objet à charger
     * @return SinapsModel l'object chargé
     */
    public function find($id) {
        $className = $this->baseModel;
        $object = $className::find($id);

        foreach( $this->relationNames as $relationName) {
            $relation = $object->$relationName;
        }

        return $object;
    }

    /**
     * Retourne une liste d'objet et charge toutes les relations
     *
     *
     * @return la liste d'objets
     */
    public function get() {
        $className = $this->baseModel;

        $request = $className::getRawRequest();

        foreach ( $this->wheres as $where) {
            $request->where($where["colonne"], $where["comparateur"], $where["valeur"], $where['func']);
        }
        foreach ( $this->orderClauses as $orderBy) {
            $request->orderBy($orderBy["clef"], $orderBy["ordre"]);
        }

        $objects = $request->get();

        foreach( $this->relationNames as $relationName) {
            $this->getRelation($relationName, $objects);
        }

        return $objects;
    }

    /**
     * Retourne un objet et charge toutes les relations
     *
     *
     * @return la liste d'objets
     */
    public function first() {
        $className = $this->baseModel;

        $request = $className::getRawRequest();

        foreach ( $this->wheres as $where) {
            $request->where($where["colonne"], $where["comparateur"], $where["valeur"], $where['func']);
        }
        foreach ( $this->orderClauses as $orderBy) {
            $request->orderBy($orderBy["clef"], $orderBy["ordre"]);
        }

        $objet = $request->first();

        // FIX : on peut pas charger les relations si l'objet est NULL
        if ($objet !== NULL) {
            foreach( $this->relationNames as $relationName) {
                $this->getRelation($relationName, array($objet));
            }
        }

        return $objet;
    }

    /**
     * Méthode permettant de charger une relation.
     *
     * Appelée recursivement pour parcourir les relations profondes
     *
     * @param String             $relationName le nom de la relation en complément du $relationPath
     * @param array<SinapsModel> $objects      les objects chargés au niveau précédent
     * @param string             $relationPath le nom du chemin précédent
     *
     * @throws OrmException Dans le cas d'un type de relation inconnu.
     *
     * @return la liste des objets chargés
     *
     * @TODO: Split cette fonction en 2: il y a un point ds le nom ou il n'y en a pas
     */
    protected function getRelation($relationName, array $objects, $relationPath="") {
        // On recherche si le nom de la relaton contient un "." Ex: rel1.rel2
        $positionPoint = strpos($relationName, '.');
        if ( $positionPoint !== FALSE) {
            // Ex: subRelation = rel1
            $subRelation = substr($relationName, 0, $positionPoint);

            if ( in_array("$relationPath.$subRelation", $this->loadedRelations) === FALSE) {
                /*
                    Si la relation père n'a pas déjà été chargée on la charge
                    Cas où on a relation1.relation2 et relation1.relation3
                    Ex: "rel1", $objects, ""
                */

                $this->getRelation($subRelation, $objects, $relationPath);
            }

            if ( in_array($subRelation, $this->subRequestIndex) === FALSE) {
                $this->subRequestIndex["$relationPath.$subRelation"] = array();

                /*
                    Si on n'a pas encore créé la liste de tous les objects correspondant à ce niveau on le fait
                    en itérant sur tous les objets du niveau-1
                    Ex: Model::with("rel1N")->get ... get donne une liste d'objet,
                    on contruit la somme de tous les 1-N des objets
                */

                foreach($objects as $object) {
                    if ($object->$subRelation !== NULL) {
                        if ( is_array($object->$subRelation) === TRUE) {
                            $this->subRequestIndex["$relationPath.$subRelation"] = array_merge(
                                $this->subRequestIndex["$relationPath.$subRelation"],
                                $object->$subRelation
                            );
                        } else {
                            $this->subRequestIndex["$relationPath.$subRelation"][] = $object->$subRelation;
                        }
                    }
                }
            }

            // Ex: resteDeLaRelation = rel2
            $resteDeLaRelation = substr($relationName, $positionPoint + 1);
            // On rappelle la fonction avec ( "rel2", cumul des rel1, "rel1")
            $this->getRelation($resteDeLaRelation, $this->subRequestIndex["$relationPath.$subRelation"], $subRelation);
            return;
        }


        // Si on est ici c'est qu'il s'agit d'une feuille (pas de . dans le nom)
        if ( empty($objects) === TRUE) {
            return $objects;
        }

        // On recupére la relation
        $relation = $objects[0]->$relationName();

        switch( $relation->getType()) {
            case SinapsRelation::HAS_ONE:
            case SinapsRelation::HAS_MANY:
                $this->getCleChezLAutre($relationName, $relation, $objects);
            break;

            case SinapsRelation::BELONGS_TO:
                $this->getCleChezSoi($relationName, $relation, $objects);
            break;

            default:
            throw new OrmException("Type de relation non supportée");
        }

        $this->loadedRelations[] = "$relationPath.$relationName";
    }

    /**
     * Méthode appelée pour charger la relation si la clef de relation est dans la classe en face.
     *
     * C'est le ca pour le fonctions (cas hasOne, hasMany)
     *
     * @param string             $relationName le nom de la relation
     * @param SinapsRelation     $relation     l'objet relation
     * @param array<SinapsModel> $objects      les objects à traiter
     * @return la liste des objets
     */
    protected function getCleChezLAutre($relationName, SinapsRelation $relation, array $objects) {
        $dstClass = $relation->getDestination();
        $fkName = $relation->getFkName();
        $type = $relation->getType();

        $srcIndex = array();
        foreach ( $objects as $object) {
            $srcIndex[$object->id] = $object;
            if ( $type === SinapsRelation::HAS_MANY) {
                $object->rawSetRelationValue($relationName, array());
            } else {
                $object->rawSetRelationValue($relationName, NULL);
            }
        }

        $dstObjects = $dstClass::whereIn($fkName, array_keys($srcIndex))->get();

        foreach( $dstObjects as $dstObject) {
            if ( $type === SinapsRelation::HAS_MANY) {
                $srcIndex[$dstObject->$fkName]->rawAddRelationValue($relationName, $dstObject);
            } else {
                $srcIndex[$dstObject->$fkName]->rawSetRelationValue($relationName, $dstObject);
            }
        }
        return $objects;
    }

    /**
     * Méthode appelée pour charger la relation si la clef de relation est dans notre classe (cas belongsTo)
     *
     * @param string             $relationName le nom de la relation
     * @param SinapsRelation     $relation     l'objet relation
     * @param array<SinapsModel> $objects      les objects à traiter
     */
	protected function getCleChezSoi($relationName, SinapsRelation $relation, array $objects) {
        $dstClass = $relation->getDestination();
        $fkName = $dstClass."_id";

        $dstIndex = array();
        foreach($objects as $object) {
            $dstIndex[$object->$fkName] = $object;
        }

        $dstObjects = $dstClass::whereIn("id", array_keys($dstIndex))->get();

        foreach( $dstObjects as $dstObject) {
            $dstIndex[$dstObject->id]->rawSetRelationValue($relationName, $dstObject);
        }
    }

    public function orderBy($clef, $ordre="ASC") {
        $this->orderClauses[] = array(
            'clef' => $clef,
            'ordre' => $ordre
        );
        return $this;
    }

    /**
     * @see OrmQuery#where
     *
     * @param string $colonne
     * @param string $comparateur
     * @param string $valeur
     * @return QueryBuilder
     */
    public function where($colonne, $comparateur, $valeur=NULL, $func=NULL) {
        $this->wheres[] = array("colonne" => $colonne, "comparateur" => $comparateur, "valeur" => $valeur, "func" => NULL);

        return $this;
    }

    /**
     * @see OrmQuery#whereIsNull
     *
     * @param string $colonne
     * @param string $comparateur
     * @param string $valeur
     * @return QueryBuilder
     */
    public function whereIsNull($colonne) {
        // TODO voir si on peut pas mieux faire
        $this->wheres[] = array("colonne" => $colonne, "comparateur" => "IS NULL AND '1'=", "valeur" => '1', "func" => NULL);

        return $this;
    }

    /**
     * @see OrmQuery#whereIsNotNull
     *
     * @param string $colonne
     * @param string $comparateur
     * @param string $valeur
     * @return QueryBuilder
     */
    public function whereIsNotNull($colonne) {
        // TODO voir si on peut pas mieux faire
        $this->wheres[] = array("colonne" => $colonne, "comparateur" => "IS NOT NULL AND '1'=", "valeur" => '1', "func" => NULL);

        return $this;
    }

    /**
     * @see OrmQuery#whereIn
     *
     * @param unknown $colonne
     * @param unknown $valeurs
     * @return QueryBuilder
     */
    public function whereIn( $colonne, $valeurs) {
        $this->wheres[] = array("colonne" => $colonne, "comparateur" => "IN", "valeur" => join( ",",$valeurs), "func" => NULL);

        return $this;
    }
    
    /**
     * @see OrmQuery#whereUpper
     *
     * @param string $colonne
     * @param string $comparateur
     * @param string $valeur
     * @return QueryBuilder
     */
    public function whereUpper($colonne) {
        // TODO voir si on peut pas mieux faire
        $this->wheres[] = array( "colonne" => $colonne, "comparateur" => $comparateur, "valeur" => $valeur, "func" => "upper");

        return $this;
    }

    /**
     * @see OrmQuery#whereLower
     *
     * @param unknown $colonne
     * @param unknown $valeurs
     * @return QueryBuilder
     */
    public function whereLower( $colonne, $valeurs) {
        $this->wheres[] = array( "colonne" => $colonne, "comparateur" => $comparateur, "valeur" => $valeur, "func" => "lower");

        return $this;
    }  

}
