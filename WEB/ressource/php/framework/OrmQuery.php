<?php
/**
 * Surcouche sur les requetes SQL.
 *
 * L'API est très fortement inspirée de Laravel 4
 * http://four.laravel.com/docs/queries
 * Seules sont implémentées les fonctions simples et les relations 1-1 et 1-N
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class OrmQuery {
    /**
     * Nom de la table en base de donnée.
     *
     * @var string
     */
    protected $tableName;
    /**
     * La classe possédant la relation.
     *
     * @var string
     */
    protected $className;
    /**
     * Ensemble des clauses WHERE.
     *
     * @var array<string>
     */
    protected $whereClauses = array();
    /**
     * Ensemble des clauses ORDER.
     *
     * @var array<string>
     */
    protected $orderClauses = array();
    /**
     * Clause LIMIT.
     *
     * @var integer
     */
    protected $limit = -1;
    /**
     * Définit le 1ier élément à récupérer.
     *
     * @var integer
     */
    protected $offset = -1;

    /**
     * Crée une nouvelle requete
     *
     * Seul le champs $tableName est obligatorire
     * Si $className n'est pas spécifié il est égal à tableName
     * Si $dbh n'est pas spécifié, on utilise l'injection avec la clef "dbConnection"
     *
     * @param string $tableName le nom de la table SQL
     * @param string $className le nom de la classe de réception des données
     * @param PDO    $dbh       une instance de PDO connectée à une BDD
     */
    function __construct($tableName, $className=NULL, PDO $dbh=NULL) {
        $this->className = ($className !== NULL) ? $className : $tableName;

        if ($dbh != NULL) {
            $this->dbh = $dbh;
        } else {
            $this->dbh = App::make('dbConnection');
        }

        // Pour Postgresql et sqlite3 (que ça dérange pas)
        $this->tableName = strtolower($tableName);
    }

    /**
     * Retourne le 1ier élément correspondant à la requete ou NULL si rien n'est rammené
     *
     * @return NULL ou objet de type $className
     */
    function first() {
        $result = $this->get(1);

        $retour = (count($result) === 1) ? $result[0] : NULL;
        return $retour;
    }

    /**
     * Retourne le 1ier élément correspondant à la requete ou une exception si rien n'est rammené
     */
    function firstOrFail() {
        // @TODO: Add Test
        $result = $this->first();

        if ($result === NULL) {
            throw new Exception("Impossible de charger un objet. table: $this->tableName");
        }

        return $result;
    }

    /**
     * retourne un tableau des éléments correspondant à la requête.
     *
     * Les éléments sont des objets de classe $this->className.
     * Une liste vide est retournée quand aucun élément ne correspond aux conditions
     *
     * @param int $extraLimit: nb max d'éléments à retourner (utilisé normalement uniquement en interne)
     *                         Utilisez plutot take @see take
     */
    function get($extraLimit=-1) {
        if ($this->dbh === NULL) {
            return array();
        }
        $request = "SELECT * FROM $this->tableName ";

        $request .= $this->expandWheres();

        if ( count($this->orderClauses) > 0) {
            $request .= " ORDER BY " . join(",", $this->orderClauses);
            $request .= " ,id ASC ";
        } else {
            $request .= " ORDER BY id ASC";
        }

        $request .= $this->expandLimit($extraLimit);
//print "REQUETE: $request\n";

        try {
            $stmt = $this->dbh->prepare($request);
            $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->className);
            $stmt->execute();

        } catch(PDOException $exception) {
            print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
            throw $exception;
        }
        $retour = $stmt->fetchAll();
        return $retour;
    }

    /**
     * Retourne le nb d'éléments matchant les critères
     *
     * @return int le nb d"élements
     */
    function count() {
        $request = "SELECT count(*) FROM $this->tableName ";

        $request .= $this->expandWheres();

        try {
// print "REQUEST: $request\n";
            $retour = $this->dbh->query($request)->fetchColumn();

        } catch(PDOException $exception) {
            print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
            throw $exception;
        }
        return $retour;
    }

    /**
     * Supprime les éléments correspondant aux conditions
     *
     * @param boolean $lowPriority demande une suppression en priorité basse
     * @return int le nombre d'éléments supprimés.
     */
    public function delete($lowPriority=FALSE) {
        $cmd = "DELETE";
        /*if ($lowPriority)
            $cmd .= " LOW_PRIORITY";*/

        $request = "$cmd FROM $this->tableName ";
        $request.= "WHERE id = any (array(SELECT id FROM $this->tableName ";
        $request .= $this->expandWheres();

    $request.= "))";

        try {
// print "REQUEST: $request\n";
        $nbEltSupprimes = $this->dbh->exec($request);

    } catch(PDOException $exception) {
            print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
            throw $exception;
        }
        return $nbEltSupprimes;
    }

    /**
     * Recharge un objet depuis la base
     * @param Mixed  $object     l'objet à mettre à jour
     * @param string $primaryKey la clef primaire en BDD
     * @return int le code retour de la BDD
     */
    function reloadObject($object, $primaryKey) {
        $request = "SELECT * FROM $this->tableName WHERE " .
                   "$primaryKey = " . $object->$primaryKey;

        $stmt = $this->dbh->prepare($request);
        $stmt->setFetchMode(PDO::FETCH_INTO, $object);
        $stmt->execute();

        $retour = $stmt->fetch();
        return $retour;
    }

    /**
     * Impose une limite au nombre d'éléments retournés par la requete.
     *
     * @param int $nombre nombre d'éléments à retourner
     * @return OrmQuery: pour le chainage
     */
    function take($nombre) {
        $this->limit = $nombre;

        return $this;
    }

    /**
     * Nombre d'éléments à sauter.
     *
     * @param int $offset le nombre
     * @return Ormquery: pour le chainage
     */
    function skip($offset) {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Ajoute une condition à la requete (les conditions sont en ET).
     *
     * Si le dernier élément n'est pas précisé alors on suppose une opération "="
     *  Ex:
     *      $query->where( "nom", "like" , "%bob%")
     *            ->where( "prenom", "bill")
     *            ->get();
     *
     * Retourne les enregistrements ayant un nom contenant bob et un prénom bill
     *
     * @param string $colonne     le nom de la colonne
     * @param string $comparateur le comparateur  ou la valeur dans le cas de l'appel à 2 paramètres
     * @param string $valeur      la valeur à rechercher
     *
     * @return OrmQuery: pour chainage
     */
    function where($colonne, $comparateur, $valeur=NULL, $func= NULL) {

        if ($valeur === NULL) {
            $valeur = $comparateur;
            $comparateur = '=';
        }
        $colonne = "\"$colonne\"";

        if($func) {
            $colonne = "$func(\"$colonne\")";
        }

        # BUG 130216 : problème du caractère joker '_' avec les clauses 'LIKE' ou 'NOT LIKE'
        # On protège systématiquement le caractère '_'
        if (strtoupper($comparateur) === 'LIKE' or strtoupper($comparateur) === 'NOT LIKE') {
            $this->whereClauses[] = "$colonne $comparateur " . self::escapeJokers($this->phpToSQL($valeur));
        } else {
            $this->whereClauses[] = "$colonne $comparateur " . $this->phpToSQL($valeur);
        }
        return $this;
    }

    /**
     * Renvoie la chaîne après avoir protégé le caracctère '_'
     * afin qu'il ne soit pas considéré comme caractère joker$
     * Cette méthode est utilisée pour les clause 'LIKE' ou 'NOT LIKE'µ
     *  - dans la méthode where de Orm
     *  - dans les reqêtes élaborées en dehors de Orm
     * @param type $str La chaîne à traiter
     * @return type
     * @see Bugzilla 130216 - sauvegardeMemcache n'escape pas
     */
    public static function escapeJokers($str) {
        $retour = $str;
        return $retour;
    }

    /**
     * Ajoute une condition WHERE xxx IN
     *
     * @param string $colonne la colonne à rechercher
     * @param array  $valeurs les valeurs possibles.
     *
     * @return OrmQuery: pour chainage
     */
    function whereIn($colonne, array $valeurs) {
        $valeursWhereIn = array();
        foreach($valeurs as $valeur) {
            $valeursWhereIn[] = $this->dbh->quote($valeur);
        }
        $this->whereClauses[] = " \"$colonne\" IN (" . join(",", $valeursWhereIn) . ")";

        return $this;
    }

    function whereIsNull($colonne) {
        $this->whereClauses[] = "\"$colonne\" is null";
        return $this;
    }

    function whereIsNotNull($colonne) {
        $this->whereClauses[] = "\"$colonne\" is not null";
        return $this;
    }

    function orWhere($colonne, $comparateur, $valeur) {

    }

    function whereUpper($colonne, $comparateur, $valeur=NULL) {
        if ($valeur === NULL) {
            $valeur = $comparateur;
            $comparateur = '=';
        }

        # BUG 130216 : problème du caractère joker '_' avec les clauses 'LIKE' ou 'NOT LIKE'
        # On protège systématiquement le caractère '_'
        if (strtoupper($comparateur) === 'LIKE' or strtoupper($comparateur) === 'NOT LIKE') {
            $this->whereClauses[] = "UPPER(\"$colonne\") $comparateur " . self::escapeJokers($this->phpToSQL($valeur));
        } else {
            $this->whereClauses[] = "UPPER(\"$colonne\") $comparateur " . $this->phpToSQL($valeur);
        }

        return $this;
    }

    function whereLower($colonne, $comparateur, $valeur=NULL) {
        if ($valeur === NULL) {
            $valeur = $comparateur;
            $comparateur = '=';
        }

        # BUG 130216 : problème du caractère joker '_' avec les clauses 'LIKE' ou 'NOT LIKE'
        # On protège systématiquement le caractère '_'
        if (strtoupper($comparateur) === 'LIKE' or strtoupper($comparateur) === 'NOT LIKE') {
            $this->whereClauses[] = "LOWER(\"$colonne\") $comparateur " . self::escapeJokers($this->phpToSQL($valeur));
        } else {
            $this->whereClauses[] = "LOWER(\"$colonne\") $comparateur " . $this->phpToSQL($valeur);
        }

        return $this;
    }

    /**
     * Ajoute une condition sans aucune transformation (opération sprintf entre $request et $values)
     *
     * @param string $request la condition. Elle peut contenir des %s (format @see sprintf)
     * @param array  $values  les valeurs
     *
     * @return OrmQuery: pour chainage
     */
    function whereRaw($request, array $values) {
        $this->whereClauses[] = vsprintf($request, $values);

        return $this;
    }

    /**
     * Ajoute un tri ascendant ou descendant sur une colonne
     *
     * @param string $clef  le nom de la colonne
     * @param string $ordre ASC ou DESC
     * @return OrmQuery pour chainage
     */
    public function orderBy($clef, $ordre="ASC") {
        // @TODO: Add test
        $this->orderClauses[] = "\"$clef\" $ordre";

        return $this;
    }

    /**
     * Insert les données $contenu en BDD
     *
     * @param Mixed $contenu tableau clef/valeur
     */
    function insert($contenu) {
        if(is_array($contenu) && array_key_exists(0, $contenu)) {
            $this->insertDeMasse($contenu);
        } else {
            $this->insertSingle($contenu);
        }
    }

    function insertDeMasse(array $tableauDeContenu) {
        // @ TODO: peut être optimisé de plein de facons:
        // ATTENTION cela exige que les colonnes soient identiques
        // PDO::prepare ou batch insert mysql ou INSERT... VALUES... VALUES...
        foreach($tableauDeContenu as $contenu) {
            $this->insertSingle($contenu);
        }
    }

    /**
     * Appelée par insert lorsqu'on ne cherche à n'enregistrer qu'un seul enregistrement.
     *
     * @param array $contenu un tableau clef/valeur des données à insérer
     */
    function insertSingle(array $contenu) {
        $cols = array();
        $values = array();

        $this->updateSequence();
        foreach($contenu as $colName => $colValue) {
            $cols[] = '"'.$colName.'"';
            $values[] = $this->phpToSQL($colValue);
        }

        $this->updateSequence();
        $this->insertPostgreSQL($cols, $values);
    }

    public function insertPostgreSQL($cols, $values) {

        $request = "INSERT INTO $this->tableName (";
        $request .= join(',', $cols).") VALUES(";
        $request .= join(',', $values).")";
//print "REQUEST: $request\n";
        try {
            $this->dbh->query($request);
        } catch(PDOException $exception) {
            print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
            throw $exception;
        }
    }

    public function insertOrUpdate($pkeyName, $pkeyValue, array $contenu) {
        $this->insertOrUpdatePostgreSQL($pkeyName, $pkeyValue, $contenu);
    }

    public function insertOrUpdatePostgreSQL($pkeyName, $pkeyValue, array $contenu) {

        /**
         * En PostgreSQL ON DUPLICATE KEY n'existe pas: contournement
         *
         * WITH upsert AS ($upsert RETURNING *) $insert WHERE NOT EXISTS (SELECT * FROM upsert);
         *
         *  ce qui donne
         *
         * WITH upsert AS (UPDATE "Utilisateur" SET "nom" = 'tata' WHERE "id"=1 RETURNING *)
         * INSERT INTO "Utilisateur" ("id", "nom", "login", "email", "password")
         * SELECT 1,'tata', 'login','email','pwd' WHERE NOT EXISTS (SELECT * FROM upsert);
         *
         */

        $cols = array($pkeyName);
        $values = array($pkeyValue);
        $updateClauses = array();
        $monId = NULL;

        foreach($contenu as $colName => $colValue) {
            $cols[] = "\"$colName\"";
            $values[] = $this->phpToSQL($colValue);
            $updateClauses[] = "\"$colName\" = " . $this->phpToSQL($colValue) ;
        }

        $request = "INSERT INTO $this->tableName (";
        $request .= join(',', $cols).") VALUES(";
        $request .= join(',', $values).")";
        $request .= " ON CONFLICT (\"$pkeyName\") DO UPDATE SET ".join(',',$updateClauses);

//print "REQUEST: $request\n";
        try {
            $this->dbh->query($request);

            $this->updateSequence();

        } catch(PDOException $exception) {
            print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
            throw $exception;
        }
    }

    public function insertOrUpdateMasse($pkeyName, array $contenu) {

        $cols = array();
        $updateClauses = array();

        foreach($contenu[0] as $colName => $colValue) {
            $cols[] = $colName;

            if ($colName !== $pkeyName)
                $updateClauses[] = "\"$colName\" = VALUES($colName)";
        }

        while($splitedValues = array_splice($contenu, 0, 100)) {
            $request = "INSERT INTO $this->tableName";
            $request .= "(\"" . join("\",\"", $cols). "\") VALUES\n";

            $count = count($splitedValues);
            for($i=0 ; $i<$count ; $i++)  {
                $values = array_map(array($this, "phpToSQL"), $splitedValues[$i]);

                $request .= "(" . implode(",", $values) . ")";

                if ($i !== $count - 1)
                    $request .= ',';
                $request .= "\n";
            }

            $request .= " ON CONFLICT (\"$pkeyName\") DO UPDATE ";
            $request .= "SET (\"" .implode("\",\"", array_values($cols))."\") = ";
            $request .= "(EXCLUDED.\"" .implode("\",EXCLUDED.\"", array_values($cols))."\")";
//print "REQUEST: $request\n";
            try {
                $this->dbh->query($request);
            } catch(PDOException $exception) {
                print "PDOException, ".$exception->getMessage()."\nrequest: $request\n";
                throw $exception;
            }
        }
    }

    /**
     * Fonctionne de la même facon que insert mais retourne l'id de l'élément inséré.
     *
     * Ne supporte pas l'insertion de masse
     *
     * @param array $contenu liste clef/valeur à insérer
     *
     * @return l'id de l'élément inséré en BDD
     */
    function insertGetId(array $contenu) {
        $this->insert($contenu);

        $retour = $this->dbh->lastInsertId($this->tableName . "_id_seq");
        return $retour;
    }

    /**
     * Fait une mise à jour des champs passés en paramètre
     *
     * @param array $contenu: les valeurs à mettre à jour.
     */
    function update(array $contenu) {
        $request = "UPDATE $this->tableName SET ";

        $updateItems = array();
        foreach ($contenu as $colonne => $valeur) {
            $updateItems[] = "\"$colonne\"  = " . $this->phpToSQL($valeur);
        }

        $request .= join(',', $updateItems);

        $request .= " ".$this->expandWheres();
// print "REQUEST= $request\n";
        $this->dbh->query($request);
    }

    /**
     * Parcours le tableau des clauses where et le transforme en SQL
     *
     * @return String: le texte SQL
     */
    private function expandWheres() {
        $request = "";

        if (count($this->whereClauses) > 0) {
            $request .= " WHERE ";
            $request .= join(" AND ", $this->whereClauses);
        }

        return $request;
    }

    /**
     * Ajout une clause limit à la requete.
     *
     * @param int $forcedLimit limite imposée même si une autre a été demandée
     * @return string clause SQL
     */
    private function expandLimit($forcedLimit=-1) {
        if ($forcedLimit === -1 &&
            $this->limit === -1)
            return "";

        $limit = " LIMIT ";
        $offset = "";

        if ($this->offset !== -1)
            $offset = " OFFSET ".$this->offset;

        if ($forcedLimit !== -1) {
            return $limit . $forcedLimit . $offset;
        }

        return $limit . $this->limit . $offset;
    }

    /**
     * Transforme une donnée PHP en format SQL.
     *
     *  Transforme un null php en NULL SQL
     *  => Ajoute des '' autour des chaines de caractère
     *
     * @param string $colonne le nom de la colonne
     * @param string $valeur  la valeur
     * @return string|unknown
     */
    public function phpToSQL($valeur) {
        // @TODO: traiter le self::escapeJokers() ici
        if ($valeur === NULL) {
            return "NULL";
        }

        if ($valeur === FALSE)
            return 0;

        if ($this->dbh !== NULL) {
            $retour = $this->dbh->quote($valeur);
            return $retour;
        }

        return $valeur;
    }

    /**
     * Mise à jour de la séquence
     *
     * @param int $nextId
     */

    public function updateSequence($nextId=NULL) {
        $sqlNextId = " (SELECT GREATEST(MAX(id)+1,nextval('".$this->tableName."_id_seq'))-1 FROM ".$this->tableName.")";
        if($nextId) {
            $sqlNextId .= $nextId;
        }
                $stmt = $this->dbh->query($sqlNextId);
                $retour = $stmt->fetchAll();
                $id = $retour[0][0];
                if($id == 0) {
                    $id =1;
                }
                $request = "SELECT setval('";
        $request.= $this->tableName;
        $request.= "_id_seq', $id)";
//print "REQUEST= $request\n";

        $this->dbh->query($request);
        return $this->getSequence();
    }



    public function getSequence() {
        $request = " SELECT CURRVAL('".$this->tableName."_id_seq');";
//print "REQUEST= $request\n";
        try {
            $stmt = $this->dbh->query($request);
            $retour = $stmt->fetchAll();
            return $retour[0]['currval'];

        } catch(PDOException $e) {
            if(strpos($e, "is not yet defined in this session") !== FALSE ) {
                return 0;
            }
        }
    }

    /**
     * Debute une transaction
     */
    public static function beginTransaction() {
        $dbConnection = App::make("dbConnection");
        $dbConnection->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public static function commit() {
        $dbConnection = App::make("dbConnection");
        $dbConnection->commit();
    }

    /**
     * Annule une transaction
     */
    public static function rollback() {
        $dbConnection = App::make("dbConnection");
        $dbConnection->rollback();
    }
}
