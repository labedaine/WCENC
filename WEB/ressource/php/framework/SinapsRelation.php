<?php
/**
 * Classe modélisant une relation 1-1, 1-N ou N-1.
 *
 * Elle est transparente pour l'utilisateur de l'ORM et accédée indirectement via SinapsModel
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class SinapsRelation {
    /** Définition des types de relation */
    const HAS_ONE = 1;
    const BELONGS_TO = 2;
    const HAS_MANY = 3;

    /**
     * SinapsModel qui a créé la relation?.
     * 
     * @var SinapsModel
     */
    protected $source;
    /**
     * Classe de destination.
     * 
     * @var string classe
     */
    protected $destination;
    /**
     * La classe de destination sans le namespace PHP.
     * 
     * @var string classe
     */
    protected $destinationSansNamespace;
    /**
     * Type de la relation.
     * 
     * @var integer
     */
    protected $type;
    
    /**
     * Constructeur
     * 
     * @param SinapsModel $source      objet source de la relation
     * @param string      $destination classe cible de la relation
     * @param int         $type        type de la relation
     */
    public function __construct(SinapsModel $source, $destination, $type) {
        $this->source = $source;
        $this->destination = $destination;
        $this->type = $type;

        $namespaces = explode("\\", $destination);
        $this->destinationSansNamespace = array_pop($namespaces);
    }

    /**
     * Fct: @TODO: Obsolète ?.
     */
    public function resolve() {
        $resultat = $this->reload();
        return $resultat;
    }

    /**
     * Réinitialise la relation provoquant une nouvelle requete en bdd au prochain accès.
     */
    public function reset() {
        $this->source->rawResetTrueRelationName($this->uniqueName());
    }

    /**
     * Force le rechargement de la relation
     * 
     * @throws OrmException En cas de type de relation inconnu.
     */
    public function reload() {
        // @TODO: Add test
        $dstClass = $this->destination;

        switch($this->type) {
            case self::HAS_ONE:
                $resultat = $dstClass::where(
                    $this->getFkName(),
                    $this->source->id
                )
                ->first();
            break;

            case self::BELONGS_TO:
                $key = $this->destinationSansNamespace."_id";
                if($this->source->$key === NULL) {
					return NULL;
				}
                $resultat = $dstClass::find($this->source->$key);
            break;
            
            case self::HAS_MANY:
                $resultat = $dstClass::where($this->getFkName(), $this->source->id)
                                     ->get();
            break;

            default:
            throw new OrmException("Utilisation d'un type de relation inconnu");
        }        

        return $resultat;
    }

    /**
     * Provoque la sauvegarde de $linkObjet et la mise à jour de la relation.
     * 
     * Valable uniquement pour les relations hasOne et hasMany
     * 
     * @param SinapsModel $linkObject l'objet à lier
     * @throws OrmException Si le type de relation n'est pas HAS_MANY ou HAS_ONE.
     */
    public function save(SinapsModel $linkObject) {
        $fkName = $this->getFkName();
        $linkObject->$fkName = $this->source->id;

        switch($this->type) {
            case self::HAS_ONE:
                $linkObject->save();

                $this->source->rawSetTrueRelationNameValue($this->uniqueName(), $linkObject);
            break;

            case self::HAS_MANY:
                $linkObject->save();

                $this->source->rawAddTrueRelationNameValue($this->uniqueName(), $linkObject);
            break;

            default:
            throw new OrmException("Utilisation de save sur une relation autre que HAS ONE ou HAS MANY");
            break;
        }
    }

    /**
     * Provoque la sauvegarde de $linkObjet et l'affectation de la foreignkey.
     * 
     * Valable uniquement pour les relations belongsTo
     * 
     * @param SinapsModel $linkObject l'objet à lier
     * @throws OrmException Si le type de relation n'est pas BELONGS_TO.
     */
    public function associate(SinapsModel $linkObject) {
        if ($this->type != self::BELONGS_TO) {
            throw new OrmException("Utilisation de associate sur une relation autre que BELONGS TO");
        } 

        if ( !is_object($linkObject)) {
            throw new OrmException(
                "Association impossible: " . 
                get_class($this->source) . 
                " vers " . 
                $this->destination
            );
        }

        $fkName = $this->getFkName($linkObject);
        $this->source->$fkName = $linkObject->id;
        $this->source->save();

        $this->source->rawSetTrueRelationNameValue($this->uniqueName(), $linkObject);
    }

    /**
     * Retourne le nom unique (technique) de la relation.
     * 
     * Relation:
     *   "Cible.Source_id" pour les hasOne et hasMany
     *   "Cible.id" pour les belongsTo
     * 
     * @throws OrmException Si la relation demandée n'existe pas.
     * @return string
     */
    public function uniqueName() {

        switch($this->type) {
            case self::HAS_ONE:
            case self::HAS_MANY:
                $fkName = $this->getFkName();
                $result = $this->destinationSansNamespace.".".$fkName;
            break;
            
            case self::BELONGS_TO:
                $result = $this->destinationSansNamespace.".id";
            break;
            
            default:
            throw new OrmException("Utilisation d'un type de relation inconnu");
        }

        return $result;
    }

    /**
     * Retourne le nom de la foreigh key
     * 
     * @param string $object: si null $this->source est utilisé
     * @return string
     */
    public function getFkName($object=NULL) {
        if ($object === NULL) {
            $object = $this->source;
        }

        $objectClass = get_class($object);
        if (property_exists($objectClass, 'table')) {
            $objectClass = $objectClass::$table;
        }

        $fkName = $objectClass."_id";
        return $fkName;
    }

    /**
     * Accesseur
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Accesseur
     */
    public function getDestination() {
        return $this->destination;
    }

    /**
     * Accesseur
     */
    public function getSource() {
        return $this->source;
    }
}
