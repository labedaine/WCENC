<?php
/**
 * Classe de base de l'ORM.
 *
 * Inspirée par Laravel 4: @see http://four.laravel.com/docs/eloquent
 * 
 * Classe de base dont doivent hériter les objets mappés sur la BDD
 * Nécessite que le nom de la table soit identique au nom de la classe 
 * (peut être modifié avec le champs static::$table)
 * 
 * Nécessite que le champs d'identification en BDD de la classe soit "id"
 * Nécessite que le champs de foreign key pour les relations soit "NomClasse_id"
 * 
 * Principes d'utilisation:
 * 
 *  $monModel = MonModel::find(1) ==> retourne l'object d'identifiant 1
 *  $objets = $monModel::where("nom", "like", "%y")
 *                     ->get();           ==> retourne tous les objets donc le nom fini par "y"
 * 
 * Gestion des relations
 *  Relation 1-N
 *  class Maitre {
 *      function maRelation1N() {
 *          return new hasMany("Esclave");
 *      }
 *  }
 *  
 *  class Esclave {
 *      function monMaitre() {
 *          return new belongsTo("Master");
 *      }
 *  }
 *  
 *  Relation 1-1
 *  class Maitre {
 *      function maRelation11() {
 *          return new hasOne("Esclave");
 *      }
 *  }
 *  
 *  class Esclave {
 *      function monMaitre() {
 *          return new belongsTo("Master");
 *      }
 *  }
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class SinapsModel {
    /**
     * Identifiant (identique pour tout les sinaps models).
     * 
     * @var boolean
     */
    public $id = FALSE;
    /**
     * Tableau des valeurs ayant été modifiée (traitementn incomplet à l'heure actuelle.
     * 
     * @var array<string>
     */
    protected $dirty = array();
    /**
     * Fait le lien entre un relation BDD et le nom pour l'application.
     * 
     * Ex: relation maRelation1N vue en entête de classe
     *  "monMaitre" => "Esclave.Master_id"
     *  
     * @var aliases hash table entre le nom public et le nom technique
     */
    protected $aliases = array();
    /**
     * Vraies relations (tableCible_id).
     * 
     * Ex: 
     *  "Esclave.Master_id" => (#Esclave#1,#Esclave#2) (objets de type esclave)
     *  
     * @var array<alias,vraieRelation> contient une hash "nomTechnique" => $objet
     */
    protected $relations = array();

    /**
     * Stocke les données ne devant pas être persistée en bdd.
     *
     * @var string<clef,valeur> 
     */
    protected $__transient = array();

    /**
     * Stocke les données converties ex: $date=2013-06-05 10:00:00, converions["date"]=1000399083.
     *
     * @var array<valeurInitiale,valeurConvertie>
     */
    protected $__conversions = array();

    /**
     * Liste des colonnes à ne pas inclure dans la vision exterieure.
     * 
     * @var array
     */
    static protected $setExclude = array("id", "dirty", "relations", "aliases", '__transient', '__conversions');

    static public $pasDeCacheDesRelations = FALSE;
    static public $resynchroAutomatiqueAvecLaBdd = FALSE;
    static protected $cache = array();
    /**
     * Constructeur
     * 
     * @param array $values: liste clef/valeur des champs à renseigner
     */
    function __construct(array $values=NULL) {
        if ($values != NULL) {
            foreach( array_keys($values) as $value) {
                $this->$value = self::sql2php($value, $values[$value]);
            }
        }

        if ($values === NULL ||
            $this->id !== FALSE) {
            $this->dirty = array();
        } else {
            $this->dirty = array_keys($values);
        }
    }

    /**
     * Retrouve un objet par sa clef primaire
     * 
     * @param int $primary_key: la clef à rechercher
     * @return NULL si aucun n'existe, objet correspondant sinon
     */
    static function find($primaryKey) {
        $calledClass = get_called_class();
        $query = new OrmQuery(self::getDatatableName(), $calledClass);

        if (array_key_exists($calledClass, static::$cache)) {
            if (array_key_exists($primaryKey, static::$cache[$calledClass])) {
                $objet = static::$cache[$calledClass][$primaryKey];
            } else { 
                $objet = $query->where("id", "=", $primaryKey)->first();
                static::$cache[$calledClass][$primaryKey] = $objet;
            }
        } else
            $objet = $query->where("id", "=", $primaryKey)->first();

        return $objet;
    }

    static function useCache() {
        static::$cache[get_called_class()] = array();
    }

    /**
     * Retourne tous les objets de la table
     * 
     * @returns array: les objets
     */
    static function all() {
        $all = self::get();
        return $all;
    }

    /**
     * Alias de all 
     */
    static function get() {
        $query = self::getRawRequest();
        $all = $query->get();
        return $all;
    }

    /**
     * Retourne le nb d'éléments matchant les critères
     * 
     * @return int le nb d"élements
     */
    static function count() {
        $query = self::getRawRequest();

        $count = $query->count();
        return $count;
    }

    /**
     * Supprime tous les éléments de la base
     *
     * @return int nb d'éléments supprimés
     */
    public static function deleteAll() {
        $query = static::getRawRequest();

        $nbEltSupprimes = $query->delete();
        return $nbEltSupprimes;
    }

    /**
     * Supprime tous les éléments de la base
     *
     * @return int nb d'éléments supprimés
     */
    public function delete() {
        $nbEltSupprimes = self::where("id", "=", $this->id)->delete();
        return $nbEltSupprimes;
    }

    /**
     * Retourne une OrmQuery portant sur cette entité
     * 
     * @return OrmQuery
     */
    static function getRawRequest() {
        $request = new OrmQuery(self::getDatatableName(), get_called_class());
        return $request;
    }

    /**
     * Retourne une OrmQuery portant sur cette entité et ayant déjà une clause where
     * 
     * @param string $colonne     la colonne à filtrer
     * @param string $comparateur le comparateur
     * @param string $valeur      la valeur recherchée
     * 
     * @return OrmQuery
     */
    static function where($colonne, $comparateur, $valeur=NULL) {
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->where($colonne, $comparateur, $valeur);

        return $ormQuery;
    }

    /**
     * Retourne une OrmQuery portant sur cette entité et ayant déjà une clause whereIn
     * 
     * @param string $colonne la colonne à filtrer
     * @param array  $valeurs les valeurs possibles
     * @return OrmQuery
     */
    static function whereIn($colonne, array $valeurs) {
        // @TODO Add test
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->whereIn($colonne, $valeurs);

        return $ormQuery;
    }

    /**
     * Retourne une OrmQuery portant sur cette entité et ayant déjà une clause where
     * 
     * @param string $colonne     la colonne à filtrer
     * @param string $comparateur le comparateur
     * @param string $valeur      la valeur recherchée
     * 
     * @return OrmQuery
     */
    static function whereUpper($colonne, $comparateur, $valeur=NULL) {
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->whereUpper($colonne, $comparateur, $valeur);

        return $ormQuery;
    }

    /**
     * Retourne une OrmQuery portant sur cette entité et ayant déjà une clause where
     * 
     * @param string $colonne     la colonne à filtrer
     * @param string $comparateur le comparateur
     * @param string $valeur      la valeur recherchée
     * 
     * @return OrmQuery
     */
    static function whereLower($colonne, $comparateur, $valeur=NULL) {
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->whereLower($colonne, $comparateur, $valeur);

        return $ormQuery;
    }

    /**
     * Méthode "magique" pour acceder à un attribut ou une relation.
     * 
     * Si il s'agit d'une relation celle-ci est dynamiquement recherchée en BDD 
     * 
     * @param String $name: attribut ou relation recherchée
     * @throws OrmException Si le $name n'est ni une relation ni un attribut.
     * @return la valeur demandée
     */
    public function &__get($name) {
        // la propriete existe, on la retourne
        if ( property_exists($this, $name)) {
            // Sauf si on a un format à appiquer 
            if (property_exists(get_called_class(), "formats") &&
                array_key_exists($name, static::$formats)) {
                // Si la conversion n'a pas encore été faite, on la fait
                if (array_key_exists($name, $this->__conversions) === FALSE) {
                    $this->__conversions[$name] = static::sql2php($name, $this->$name);
                }
                return $this->__conversions[$name];
            }
            return $this->$name;
        }

        // la propriete est un alias et la valeur cible est renseignée
        if ( array_key_exists($name, $this->aliases) &&
             array_key_exists($this->aliases[$name], $this->relations) &&
             static::$pasDeCacheDesRelations) {
            return $this->relations[$this->aliases[$name]];
        }

        // Il existe une methode equivalente ==> c'est une relation
        if (method_exists($this, $name)) {
            $this->resoudreRelation($name);
            return $this->relations[$this->aliases[$name]];
        }

        // Rien n'a fonctionné: lancement d'une OrmException
        throw new OrmException(
            "la propriete $name  de ". get_called_class() .
            " n'est ni une relation, ni un attribut"
        );
    }

    /**
     * Affecte une valeur à un attribut.
     * 
     * Ne marche pas sur les relations.
     * Il est par contre possible de setter une foreigh key
     *  Ex: 
     *      $myMaster->maRelation1N         ==> ne fonctionne pas
     *      $myEsclave->Master_id = 3       ==> fonctionne
     * 
     * @param String $name   le nom de l'attribut
     * @param String $valeur la valeur de l'attribut
     */
    public function __set($name, $valeur) {
        $valeurTransformee = self::sql2php($name, $valeur);
        if ($valeurTransformee !== $valeur) {
            $this->__conversions["$name"] = $valeurTransformee;
        }
        $this->$name =& $valeur;

        $this->dirty[] = $name;
    }

    /**
     * permet de stocker des valeurs qui ne seront pas persistée en BDD
     */
    public function setTransient($name, $value) {
        $this->__transient[$name] = $value;
    }

    /**
    * Permet de récupérer les valeurs créées via setTransient
    */
    public function getTransient($name) {
        if (array_key_exists($name, $this->__transient)) {
            return $this->__transient[$name];
        } else {
            return NULL;
        }
    }

    /**
     * Persiste les attributs du modèle.
     * 
     * Fait un insert ou un update suivant que l'objet existant précédement ou pas.
     */
    public function save() {
        if ( empty($this->dirty) === TRUE) {
            return;
        }

        $query = new OrmQuery(self::getDatatableName());

        $valeursModifiees = array();
        foreach( $this->dirty as $dirty) {
            $valeursModifiees[$dirty] = self::php2Sql($dirty, $this->$dirty);
        }

        if ($this->id === FALSE) {
            // Cas d'une création
            $this->id = $query->insertGetId($valeursModifiees);
        } else {
            // Cas d'une mise à jour            
            $query  ->where("id", $this->id)
                    ->update($valeursModifiees);
        }

        $this->dirty = array();

        if (static::$resynchroAutomatiqueAvecLaBdd)
            $this->reload();
    }

    static public function insertOrUpdateMasse($pkeyName, array $valeurs) {
        $query = new OrmQuery(self::getDatatableName());

        $query->insertOrUpdateMasse($pkeyName, $valeurs);
    }


    /** 
     * Force le recharchement des données depuis la BDD
     */
    public function reload() {
        $query = new OrmQuery(self::getDatatableName(), get_called_class());
        $query->reloadObject($this, "id");
    }

    /**
     * Force un insert de l'nsemble des colonnes dont l'id
     */
    public function forcedSave() {
        $query = new OrmQuery(self::getDatatableName());

        $allProperties = $this->toArray();
        $query->insert($allProperties);
    }

    /**
    * Provoque une mise à jour ou une création de l'objet.
    *
    * L'id est préservé dans le cas d'un update
    *
    * @param array $exclusionList Liste des colonnes à ne pas mettre à jour 
    * (elles doivent être NULLABLE ou DEFAULT pour les inserts)
    */
   public function insertOrUpdate(array $excludeListe=array()) {
        $query = new OrmQuery(self::getDatatableName());

        $allProperties = $this->toArray(array("id"));
        $updateProperties = array();
        foreach($allProperties as $propertyName => $propertyValue) {
            if (!in_array($propertyName, $excludeListe))
                $updateProperties[$propertyName] = self::php2Sql($propertyName, $propertyValue);
        }

        $query->insertOrUpdate("id", $this->id, $updateProperties); 
    }

    /**
     * Permet le eager fetching: chargement en masse des données adjacentes
     * 
     * Peut prendre plusieurs relation en paramètre séparées par des ,
     * Peut suivre les relations en les séparant par des .
     * 
     *  "maRelation.uneRelationDeMaRelation,monAutreRelation"
     * 
     * Voir la document de laravel pour plus détails
     * http://four.laravel.com/docs/eloquent#eager-loading
     * 
     * Ex: 
     *  Master::with("maRelation1N)->get();
     * 
     * fera 2 requetes 
     *  select * from Master;
     *  select * from Slave where Master_id IN (..,..,..,..)
     *  
     *  Alors que 
     *  
     *  foreach( Master::get() as $master) {
     *      print $master->maRelation1N->nom;
     *  }
     *  fera autant que requete que nombre de Master
     *  select * from Master;
     *  select * from Slave where Master_id = ..
     *  select * from Slave where Master_id = ..
     *  select * from Slave where Master_id = ..
     *  ...
     *  
     * @param string $relationNames nom public des relations
     * @return QueryBuilder
     */
    public static function with($relationNames) {
        $builder = new QueryBuilder(get_called_class());

        foreach( explode(",", $relationNames) as $relationName) {
            $builder->addRelation($relationName);
        }

        return $builder;
    }

    /**
     * Méthode chargée de retourner la valeur d'une relation.
     * 
     * Peut déclencher une requete SQL quand la valeur n'a jamais été chargée
     * 
     * @param String $relation: le nom public de la relaiton
     * @return la valeur de la relation
     */
    private function resoudreRelation($relation) {
        $relationObj = $this->$relation();
        $relationUniqueName = $relationObj->uniqueName();
        if ( array_key_exists($relationUniqueName, $this->relations) === FALSE ||
             static::$pasDeCacheDesRelations) {
            // La relation n'a pas encore été chargée, on recupère sa valeur
            $this->relations[$relationUniqueName] = $this->$relation()->resolve();
        } 
        // Création d'un alias entre la relation demandée et sa clef réelle
        $this->aliases[$relation] = $relationUniqueName;
        return $this->relations[$relationUniqueName];
    }

    /**
     * Renvoie la liste des champs d'un objet Orm
     * Le champ "id" (s'il n'est pas exclu) est forcé à la première position
     * @param type $exclureChampId Si vrai, la liste ne contiendra pas le champ id
     * @return type
     */
    public function getListeChamps($exclureChampId = FALSE) {
        // Eléments interne à Orm à exclure du retour
        $listeExclusions = array('dirty', 'aliases', 'relations', '__transient', '__conversions');
        $listeProperties = array_keys(get_object_vars($this));
        // Si le champ id existe, on le force en première position
        if (in_array('id', $listeProperties)) {
            $listeProperties = array_diff($listeProperties, array('id'));
            // On ne le remet que si $exclureChampId est FAUX
            if (! $exclureChampId) {
                array_unshift($listeProperties, "id");
            }
        }
        // On exclut les éléments internes ($listeExclusions)
        $retour = array_diff($listeProperties, $listeExclusions);
        return $retour;
    }
    
    /**
     * Retourne la liste de tous les champs ayant été modifiés.
     * 
     * Mal géré à l'heure actuelle
     * 
     * @return multitype:
     */
    public function getDirty() {
        return $this->dirty;
    }

    /**
     * Déclare une relation 1-1 dont la clef est dans l'objet cible
     * 
     * @param String $destination: le nom de la classe destination
     * @return SinapsRelation: object SinapsRelation correspondant à la relation
     */
    protected function hasOne($destination) {
        $relation = new SinapsRelation($this, $destination, SinapsRelation::HAS_ONE);    
        return $relation;
    }
    


    /**
     * Déclare une relation 1-1 ou 1-N dont la clef est dans l'objet
     * 
     * @param String $destination: le nom de la classe destination
     * @return SinapsRelation: object SinapsRelation correspondant à la relation
     */
    protected function belongsTo($destination) {
        $relation = new SinapsRelation($this, $destination, SinapsRelation::BELONGS_TO);
        return $relation;
    }

    /**
     * Déclare une relation 1-N dont la clef est dans l'objet cible
     *
     * @param String $destination: le nom de la classe destination
     * @return SinapsRelation: object SinapsRelation correspondant à la relation
     */
    
    protected function hasMany($destination) {
        $relation = new SinapsRelation($this, $destination, SinapsRelation::HAS_MANY);
        return $relation;
    }

    /**
     * Retourne le nom de la table en BDD correspondant à ce modèle.
     * 
     * Egale le nom de la classe si static:$table n'est pas défini.
     * 
     * @return string
     */
    static protected function getDatatableName() {
        $result = get_called_class();
        if (property_exists($result, "table")) {
            $result = $result::$table;
        }

        return $result;
    }

    /**
     * Permet d'appliquer des transformation sur une colonne de php vers le SQL.
     * 
     * Nécessite que le champs static::formats soit renseigné.
     * 
     * Format supportés: 
     *  - timestamp : transforme un DATETIME SQL en timestamp UNIX
     *  
     * @param String $colonne le nom de la colonne
     * @param Mixed  $valeur  la valeur de l'attribut
     * @return la valeur
     */
    static protected function php2Sql($colonne, $valeur) {
        $className = get_called_class();

        if (property_exists($className, "formats")){
            if ( array_key_exists($colonne, $className::$formats)) {
                return static::applyFormat($className::$formats["$colonne"], $valeur);
            }
        }

        return $valeur;
    }

    /**
     * Permet d'appliquer des transformation sur une colonne de php vers le SQL 
     * 
     * Format supportés: 
     *  - timestamp : transforme un DATETIME SQL en timestamp UNIX
     *  
     * @param String $type   le type de format
     * @param Mixed  $valeur la valeur de l'attribut
     * @return la valeur formattée
     */
        static protected function applyFormat($type, $valeur) {
        $result = FALSE;
       
        if(!is_numeric($valeur)) {
            return $valeur;
        }

        switch($type) {
            case "timestamp":
                $result = date("Y-m-d H:i:s", $valeur);
            break;

            default: 
            throw new Exception("Le type $type n'est pas supporté par l'ORM");
        }

        return $result;
    }

    /**
     * Permet d'appliquer des transformation sur une colonne de SQL vers le php.
     * 
     * Nécessite que le champs static::formats soit renseigné.
     *
     * Format supportés:
     *  - timestamp : transforme un DATETIME SQL en timestamp UNIX
     *
     * @param String $colonne le nom de la colonne
     * @param Mixed  $valeur  la valeur de l'attribut
     * @return la valeur
     */
    static protected function sql2php($colonne, $valeur) {
        $className = get_called_class();

        if (property_exists($className, "formats")){
            if ( array_key_exists($colonne, $className::$formats)) {
                $valeurTransformee = self::revertFormat($className::$formats["$colonne"], $valeur);
                return $valeurTransformee;
            }
        }

        return $valeur;
    }

    /**
     * Permet d'appliquer des transformation sur une colonne de SQL vers le PHP
     *
     * Format supportés:
     *  - timestamp : transforme un DATETIME SQL en timestamp UNIX
     *
     * @param String $type   le type de l'attribut
     * @param Mixed  $valeur la valeur de l'attribut
     * @return la valeur formattée
     */
    static protected function revertFormat($type, $valeur) {
        $result = FALSE;
        switch($type) {
            case "timestamp":
                if ( is_numeric($valeur) === TRUE) {
                    $result = $valeur;
                } else {
                    $result = strtotime($valeur);
                }
            break;

            default: 
            throw new Exception("Le type $type n'est pas supporté par l'ORM");
        }

        return $result;
    }

    /**
     * Ajoute directement un objet à une relation.
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName le nom "public" de la relation
     * @param Object $dstObject    l'objet à inserer
     */
    public function rawAddRelationValue($relationName, $dstObject) {
        if (array_key_exists($relationName, $this->aliases) === FALSE) {
            $relation = $this->$relationName();
            $this->aliases[$relationName] = $relation->uniqueName();
        }

        $this->relations[$this->aliases[$relationName]][] = $dstObject;
    }

    public function rawSetRelationValueToNull($relationName) {
        if (array_key_exists($relationName, $this->aliases) === FALSE) {
            $relation = $this->$relationName();
            $this->aliases[$relationName] = $relation->uniqueName();
        }

        $this->relations[$this->aliases[$relationName]] = array();
    }

    /**
     * Affecte directement un objet à une relation.
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName le nom "public" de la relation
     * @param Object $dstObject    l'objet à inserer
     */
     public function rawSetRelationValue($relationName, $dstObject) {
        if (array_key_exists($relationName, $this->aliases) === FALSE) {
            $relation = $this->$relationName();
            $this->aliases[$relationName] = $relation->uniqueName();
        }

        $this->relations[$this->aliases[$relationName]] = $dstObject;
    }

    /**
     * Affecte directement un objet à une relation.
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName le nom "privé" de la relation
     * @param Object $dstObject    l'objet à inserer
     */
    public function rawSetTrueRelationNameValue($trueRelationName, $dstObject) {
        $this->relations[$trueRelationName] = $dstObject;
    }

    /**
     * Affecte directement un objet à une relation.
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName le nom "privé" de la relation
     * @param Object $dstObject    l'objet à inserer
     */
    public function rawAddTrueRelationNameValue($trueRelationName, $dstObject) {
        $this->relations[$trueRelationName][] = $dstObject;
    }

    /**
     * Récupère la valeur d'une relation à partir de son nom "privé"
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName: le nom "privé" de la relation
     * @return SinapsModel le modèle correspondant à la relation
     */
    public function rawGetTrueRelation($relationName) {
        if ( array_key_exists($relationName, $this->relations)) {
            return $this->relations[$relationName];
        }

        return NULL;
    }

    public function rawResetTrueRelationName($relationName) {
        unset($this->relations[$relationName]);
    }
    /**
     * Retourne true si la relation a déjà été chargée
     * 
     * Utilisation interne uniquement - Utilisation dans QueryBuilder et OrmQuery
     * 
     * @param String $relationName: le nom "public" de la relation
     * @return boolean true si la relation a déjà été chargée
     */
    public function isRelationLoaded($relationName) {
        $isLoaded = (array_key_exists($relationName, $this->aliases) &&
                     array_key_exists($this->aliases[$relationName], $this->relations));

        return $isLoaded;
    }


    /**
     * Retourne un tableau associatif contenant les champs de la classe.
     * 
     * Exclu les champs aliases/dirty/relations ainsi que le tableau passé en paramètre
     * (par exemple en surchargant la méthode on peut exclure le champs password @see Utilisateur
     * 
     * @param array<String> $excludeListe: liste des champs à exclure
     * @return array<String,String>
     */
    public function toArray(array $excludeListe=array()) {
        // @TODO: Add test
        $array = array();
        $allProperties = array_keys(get_object_vars($this));

        $demandeExpliciteDExclusionDeId = in_array('id', $excludeListe);

        $excludeListe["id"] = "id";
        $excludeListe["aliases"] = "aliases";
        $excludeListe["dirty"] = "dirty";
        $excludeListe["relations"] = "relations";
        $excludeListe["__transient"] = "__transient";
        $excludeListe["__conversions"] = "__conversions";

        $allProperties = array_diff($allProperties, $excludeListe);
        if (!$demandeExpliciteDExclusionDeId) {
            array_unshift($allProperties, "id"); // On force id a etre en 1ier
        }

        foreach ($allProperties as $property) {
            $array[$property] = $this->$property;
        }
        return $array;
    }
//    public function toArray(array $excludeListe=array()) {
//        // @TODO: Add test
//        $array = array();
//        $demandeExpliciteDExclusionDeId = in_array('id', $excludeListe);
//
//        $allProperties = $this->getListeChamps($demandeExpliciteDExclusionDeId);
//        $listeFiltree = array_diff($allProperties, $excludeListe);
//
//        foreach ($listeFiltree as $property) {
//            $array[$property] = $this->$property;
//        }
//        return $array;
//    }

    public function __sleep() {
        return array_keys($this->toArray());
    }

    /**
     * Similaire à toArray.
     * 
     * Applique automatiquement les méthodes format<Champs> si elles existent
     * 
     * @param array<string> $excludeListe champs à ne pas exporter
     * @return Ambigous <multitype:, multitype:>
     */
    public function toFormattedArray(array $excludeListe=array()) {
        //@TODO: Add test
        $fields = $this->toArray($excludeListe);
        foreach($fields as $nom => $valeur) {
            $formatterName = "format".ucfirst($nom);
            if (method_exists($this, $formatterName)) {
                $fields[$nom] = $this->$formatterName();
            }
        }

        return $fields;
    }
    
	/**
     * Met à jour l'id de sequence de la table
     * 
     * @param string $colonne     la colonne à filtrer
     * 
     * @return OrmQuery
     */
    static function updateSequence($nextId=NULL) {
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->updateSequence($nextId);

        return $ormQuery;
    }
    
    /**
     * return jour l'id de sequence de la table
     * 
     * @param string $colonne     la colonne à filtrer
     * @param string $comparateur le comparateur
     * @param string $valeur      la valeur recherchée
     * 
     * @return OrmQuery
     */
    static function getSequence() {
		
        $ormQuery = new OrmQuery(self::getDatatableName(), get_called_class());
        $ormQuery = $ormQuery->getSequence();

        return $ormQuery;
    }
    
}
