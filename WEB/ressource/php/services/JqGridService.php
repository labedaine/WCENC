<?php
/**
 * Service permettant de renvoyer un Json formatté selon les conventions JqGrid.
 *
 * Ce service comprend également les paramètres envoyés par JqGrid
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class JqGridService {

    protected $dateService;
    protected $jsonService;
    /** @var options: liste des options possibles */
    private $options = array(
        'sidx' => FALSE,    // Colonne de tri
        'sord' => FALSE,    // Sens du tri (ASC ou DESC)
        'oper' => FALSE,    // Operateur de recherche (recherche uni champ)
        'value' => FALSE,   // Valeur de recherche (recherche uni champ)
        'field' => FALSE,   // Champ à rechercher (recherche uni champ)
        'rows' => FALSE,    // Nb de ligne par page
        'page' => 0,    // Page à afficher
        /**
         * Nombre de ligne maximum que le service renverra
         *  Si cette valeur est égale à 0, le service renverra toutes les lignes (indépendamment des filtres éventuels)
         * Cette option peut ètre modifiée avec le $objet->setConditions(array('maxRows' => nouvelleValeur);
         */
        'maxRows' => 1000,
        'fields' => array() // Recherche multi champs
    );

    private $callbacks = array();

    /** @var opers: liste des opérations permises par JqGrid et leur équivalent en code PHP */
    private $opers = array(
        'eq' => "'%s' == '%s'",                                                         // equal
        'ne' => "'%s' != '%s'",                                                         // not equal
        'gt' => "'%s' > '%s'",                                                          // greater than
        'lt' => "'%s' < '%s'",                                                          // less than
        'ge' => "'%s' >= '%s'",                                                         // greater equal
        'le' => "'%s' <= '%s'",                                                         // less equal
        'bw' => 'strncmp(\'%1$s\', \'%2$s\', strlen(\'%2$s\')) == 0',                   // begins with
        'ew' => 'strrpos(\'%1$s\', \'%2$s\') === strlen(\'%1$s\')-strlen(\'%2$s\')',    // ends with
        'cn' => 'strlen(strstr(\'%1$s\',\'%2$s\'))>0',                                  // contains
        'bn' => '!strncmp(\'%1$s\', \'%2$s\', strlen(\'%2$s\')) == 0',                  // does not begin with
        'en' => '!(strrpos(\'%1$s\', \'%2$s\') === strlen(\'%1$s\')-strlen(\'%2$s\'))', // does not end with
        'nc' => '!(strlen(strstr(\'%1$s\',\'%2$s\'))>0)',                               // does not contain
        'in' => 'strpos(\'%2$s\',\'%1$s\') !== FALSE',                                  // is in
        'ni' => 'strpos(\'%2$s\',\'%1$s\') === FALSE',                                  // is not in
        // rg est utilisé pour contains avec un caratère joker (*) (cf: appliquerFiltreMultiples)
        'rg' => 'preg_match(\'/%2$s/\',\'%1$s\') != FALSE'                             // regexp
    );

    /** @var $tabData: tableau des éléments à retourner    */
    private $tabData = array();
    /** @var $totalCount: nombre total d'éléments à renvoyer */
    private $totalCount = 0;
    /** 
     * @var $totalReelCount: nombre total réel d'éléments :
     * Peut être supérieur à $totalCount dans le cas où on 
     * utilise une requête avec LIMIT et un offset
     *  => il interferera donc sur le calcul des pages
     * CF : utilisé dans l'écran  des dérogations
     */
    private $totalReelCount = 0;
    
    /**
     *
     */
    public function __construct() {
        $this->options['page'] = 1;
        $this->options['rows'] = 1000;
        $this->dateService = SinapsApp::make("DateService");
        $this->jsonService = SinapsApp::make("JsonService");
    }

    /**
     * Crée une réponse json en fonction des objects à retourner et des $conditions à appliquer
     *
     * @param array<SinapsModel> $objects
     * @param array $conditions
     * @param array $attributsAExclure
     * @param boolean $aFiltrer
     * @param array $champDate
     * @param array $champGrouping nomDuChamp => SORT_DESC ou SORT_ASC
     * @param array $champAExclureFiltre
     * @return String: le json
     */
     
    public function createResponseFromModels(
			$objects, 
			$conditions=array(), 
			$attributsAExclure=array(), 
			$aFiltrer=FALSE, 
			$champDate=array("date"), 
			$champGrouping=NULL,
			$champAExclureFiltre=array()
    ) {
        $this->tabData = array();
        $this->totalCount = 0;
        $this->options['sidx'] = FALSE;
        $this->options['sord'] = FALSE;

        if ( count($objects) > 0) {

			if (array_key_exists("beforeFiltrage", $this->callbacks)) {
				$callback = $this->callbacks["beforeFiltrage"];
				$callback($objects, $this->options['fields']);
			}

            if ( count($conditions) > 0) {
                $this->setConditions($conditions);
            }

            foreach( $objects as $object) {
                $this->tabData[] = $object->toFormattedArray($attributsAExclure);
            }

            if ( empty($this->options["fields"])) {
                $this->options["fields"] = array_keys($this->tabData[0]);
            }

            if( $aFiltrer ) {
                $this->getFilteredArray($champDate, $champAExclureFiltre);
            }

            $this->getOrderedArray($champGrouping);
        }

        $retour = $this->getJsonResponse();

        return $retour;
    }

    /**
     * Crée une réponse json en fonction des objects à retourner et des $conditions à appliquer
     * Les filtrages et tris ont déjà été effectués par l'appelant
     * via la requête SQL
     *
     * @param array<SinapsModel> $objects
     * @param array $conditions
     * @param array $attributsAExclure
     * @param type $totalReelLignes correspond au nombre  réel d'enregistrements par rapport aux critères
     * On renseigne cet argument dans le cas d'utilisation de LIMIT (cf voir DerogationController)
     * @return String: le json
     */
     
    public function createResponseFromModelsAvecOffset(
                                                            $objects, 
                                                            $conditions=array(), 
                                                            $attributsAExclure=array(), 
                                                            $totalReelLignes = 0
    ) {
        $this->tabData = array();
        $this->totalCount = 0;
        $this->totalReelCount = $totalReelLignes;
        $this->options['sidx'] = FALSE;
        $this->options['sord'] = FALSE;

        if ( count($objects) > 0) {

            if ( count($conditions) > 0) {
                $this->setConditions($conditions);
            }

            foreach( $objects as $object) {
                $this->tabData[] = $object->toFormattedArray($attributsAExclure);
            }

            if ( empty($this->options["fields"])) {
                $this->options["fields"] = array_keys($this->tabData[0]);
            }
        }

        $retour = $this->getJsonResponse();

        return $retour;
    }
    
    /**
     * Enregistre une fonction de callback sur événement
     * Actuellement supporté:
     *      - onFiltrageTermine: appliqué après que les critères de recherche ai été appliqués
     *      - beforeFiltrage:    appliqué entre le retour sql et le filtrage
     * @param String $callbackName
     * @param Closure $callbackFunction
     * @return void
     */
    public function registerCallBack($callbackName, Closure $callbackFunction) {
        $this->callbacks[$callbackName] = $callbackFunction;
    }

    /**
     * Permet de définir les conditions applicables
     *
     * @param array $conditions
     */
    public function setConditions($conditions) {
        // @TODO: utiliser un array_???
        foreach( $conditions as $nom => $valeur) {
            $this->options[$nom] = $valeur;
        }
    }

    /**
     * Renvoie le tableau des données
     * @return array
     */
    public function getAllArray() {
       return $this->tabData;
    }

    /**
     * Renvoie le nombre total d'elements du tableaux
     * @return int nombre d'element(s)
     */
    public function getCount() {
        if ($this->totalReelCount > 0
                && $this->totalReelCount > $this->totalCount) {
            $retour = $this->totalReelCount;
        } else {
            $retour = $this->totalCount;
        }
        return $retour;
    }

    /**
     * Renvoie le bout de tableau sélectionné par l'utilisateur de la jqGrid
     * en fonction de la page de la jqGrid et du nombre d'elements désirés
     * @param type $start
     * @param type $count
     * @return type
     */
    public function getLimiteIndArray($start, $count) {

        $tabRes = array();

        If ($count > count($this->tabData)) {
            $count = count($this->tabData);
        }

        $tabRes = array_slice($this->tabData, $start, $count);

        return $tabRes;
    }

    /**
     * Renvoie le resultat de la grille au format json
     * @return string json
     */
    private function getJsonResponse() {
        $page     = $this->options['page'];
        $rows     = $this->options['rows'];

        // Limitation du total de lignes à maxRows lignes
        $nbLignesDatas = count($this->tabData);
        $this->totalCount = $nbLignesDatas;
        if ($this->options['maxRows'] > 0) {
            if ($nbLignesDatas > $this->options['maxRows']) {
                $this->tabData = array_slice($this->tabData, 0, $this->options['maxRows']);
                $nbLignesDatas = count($this->tabData);
                $this->totalCount = $nbLignesDatas;
            }
        }
        // Calcul page courante / total pages
        $count = $this->getCount();
        if( $page > ceil($count / $rows) ) {
            $page = 1;
        }
        if( $count > 0 ) {
            $totalPages = ceil($count / $rows);
        } else {
            $totalPages = 0;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        
        // Si on est sur un tableau préfiltré par LIMIT et offset, on prend la totalité des lignes
        if ($this->totalReelCount > 0) {
            $arrRes = $this->tabData;
        } else {
            $start = ($page - 1) * $rows;
            $arrRes = $this->getLimiteIndArray($start, $rows);
        }

        if (array_key_exists("onFiltrageTermine", $this->callbacks)) {
            $callback = $this->callbacks["onFiltrageTermine"];
            $callback($arrRes, $this->options['fields']);
        }

        $json = new stdClass();
        $json->records = $count;
        $json->page = $page;
        $json->total = $totalPages;

        $idxLigne = 0;
        $json->rows = array();
        foreach($arrRes as $row) {
            $json->rows[$idxLigne]['id'] = $this->getRowId($row);
            $json->rows[$idxLigne]['cell'] = $this->getRow($row);
            $idxLigne++;
        }

        $retour = json_encode($json);
        return $retour;
    }


    /**
     * @param array $row la ligne a traité
     * @return array la ligne formaté
     */
    function getRow($row) {
        $retour = array_values($row);
        return $retour;
    }

    /**
     * @param array $row
     * @return string identifiant unique de la ligne
     */
    function getRowId($row) {
        return $row["id"];
    }

    /**
     * Renvoie un champs s'il existe en fournissant son nom
     *
     * @param type $fieldName nom du champs
     * @return string $fieldName s'il existe sinon FALSE
     */
    private function getField($fieldName) {

        if ($this->options['fields']) {
            if (in_array($fieldName, $this->options['fields'])) {
                return $fieldName;
            } else {
                return FALSE;
            }
        }
    }

    /**
     * Retourne le tableaux de donnée filtré avec les informations de
     * filtrage paramétrées par l'utilisateur dans les filtres de la jqGrid
     *
     * @param array $champDate
     */
    private function getFilteredArray($champDate, $champAExclureFiltre) {
        if ( isset($this->options['_search']) &&
             $this->options['_search'] !== "false") {

            if ( isset($this->options['filters']) &&
                 $this->options['filters']) {
                 // Recherche multiple
                $this->appliquerFiltreMultiples($champDate, $champAExclureFiltre);
            } else {
                $this->appliquerFiltreSimple($champAExclureFiltre);
            }
        }
    }

    /**
     * Applique les filtres si la grid est configurée en multicritère
     *
     * @param array $champDate
     */
    protected function appliquerFiltreMultiples($champDate, $champAExclureFiltre) {
        // @TODO: découper la méthode
        // @TODO: ne gère pas les sous-groupes :
        // http://www.trirand.com/jqgridwiki/doku.php?id=wiki%3aadvanced_searching#options
        $multipleSearch = json_decode($this->options['filters']);
        $groupeCond = $multipleSearch->groupOp;
            
        $rulesResult = array();
        foreach($multipleSearch->rules as $rule) {

            $fieldName = $rule->field;
            $oper = $rule->op;
            $value = $rule->data;

            if( $oper === 'cn' ) {
                // Vérification de l'utilisation du caractère * avec op == 'cn'
                if( strpos($value, '*') !== FALSE ) {
                    $value = str_replace('*', '.*', $value);
                    $oper = 'rg';
                }
            } else {
                if (in_array($oper, array('gt', 'lt', 'ge', 'le'))) {
                    // Cas de la date (On modifie pour tester la date)
                    $value = $this->dateToTimestamp($fieldName, $value, $champDate, $oper);
                }
            }

            // Evaluation de la règle
            $resArray = array();
            foreach($this->tabData as $name => $ind) {

				if(in_array($fieldName, $champAExclureFiltre)) {
					$resArray[$name] = $ind;
			    	continue;
			    }
			
                $strOnWhatToSearch = $ind[$fieldName];
                if(method_exists(get_class($this), 'searchFormat'.ucfirst($fieldName))) {
                    $strOnWhatToSearch = call_user_func(
                        array(
                            get_class($this),
                            'searchFormat'.ucfirst($fieldName)
                        ),
                        $ind[$fieldName]
                    );
                }

                $strOnWhatToSearch = $this->dateToTimestamp($fieldName, $strOnWhatToSearch, $champDate, $oper);

                // Cas de la date (On modifie pour tester la date)
                if( $oper === 'cn' ) {
                    $strOnWhatToSearch = $this->timestampToDate($fieldName, $strOnWhatToSearch, $champDate);
                }

                $eval = 'return (' . sprintf(
                    $this->opers[$oper],
                    addslashes(strtoupper($strOnWhatToSearch)),
                    addslashes(strtoupper($value))
                ) . ');';

                if (eval($eval)) {
                    $resArray[$name] = $ind;
                }
            }
            $rulesResult[] = $resArray;
        }

        /** On fusionne / intersecte les tableaux de résultats de chaque règle
         * en fonction de l'opérateur pour les règles
         */
        if ($groupeCond == 'AND') {
            $arrResult = NULL;
            foreach($rulesResult as $rr) {
                if (! is_null($arrResult)) {
                    // BZ 138941 : PHP Notice sur conversion array to string
//                    $arrResult = array_intersect_assoc($rr, $arrResult);
                    $arrResult = PhpFunctions::array_intersect_assoc($rr, $arrResult);
                } else {
                    $arrResult = $rr;
                }
            }

        } else if ($groupeCond == 'OR') {

            $arrResult = array();
            foreach($rulesResult as $rr) {
                foreach($rr as $index => $values) {
                    $arrResult[$index] = $values;
                }
            }
        }

        $this->tabData = $arrResult;
    }

    /**
     * Applique les filtres si le jqGrid est configurée en mono champ
     */
    protected function appliquerFiltreSimple($champAExclureFiltre) {

        $fieldName = $this->getField($this->options['searchField']);
        $value = $this->options['searchString'];
        $oper = $this->options['searchOper'];

        $resArray = array();
        if ($oper && $value && $fieldName) {
            foreach($this->tabData as $name => $ind) {
				
                $strOnWhatToSearch = $ind[$fieldName];
                if(method_exists(get_class($this), 'searchFormat'.ucfirst($fieldName))) {
                    $strOnWhatToSearch = call_user_func(
                        array(
                            get_class($this),
                            'searchFormat'.ucfirst($fieldName)
                        ),
                        $ind[$fieldName]
                    );
                }
                $eval = 'return (' . sprintf(
                    $this->opers[$oper],
                    strtoupper($strOnWhatToSearch),
                    strtoupper($value)
                ) . ');';
                if (eval($eval)) {
                    $resArray[$name] = $ind;
                }
                
            }
            $this->tabData = $resArray;
        }
    }

    /**
     * Renvoie le tableaux dans l'ordre selectionné par l'utilisateur
     * dans la jqGrid (clique sur l'entete de colonne )
     *
     * @param $champGrouping: champ à trier pour grouping jqgrid (ex: Nouvelles Alertes)
     */
    private function getOrderedArray($champGrouping=NULL) {
        // De la spec JqGrid:
        // the sidx parameter contain the order clause. It is a comma separated string in format field1 asc,
        // field2 desc …, fieldN.
        // Note that the last field does not not have asc or desc. It should be obtained from sord parameter
        $allSorts = $this->options['sidx'] . ' ' . $this->options['sord'];

        $multiSortParams = array();
        $tab = array();
        foreach(explode(',', $allSorts) as $oneSort) {
            $tmp = explode(' ', trim($oneSort));
            $col = $this->getField($tmp[0]);
            // Gestion du cas oû un grid ne gère pas le tri
            if ($col !== false) {
                $order = (strtoupper($tmp[1]) == 'ASC') ? 'SORT_ASC' : 'SORT_DESC';
                if ($champGrouping == NULL || $col != $champGrouping[0]) {
                    $multiSortParams[] = '$tab["' . $col . '"],' . $order;
                } else {
                    array_unshift($multiSortParams, '$tab["' . $col . '"],' . $champGrouping[1]);
                }
                $tab[$col] = array();
                foreach($this->tabData as $ind) {
                    $tab[$col][] = strtoupper($ind[$col]);
                }
            }
        }

        // Créer la ligne d'invocation de array_multisort, cette ligne modifiera $this->tabData
        // sur la base des tris des premières colonnes
        if (count($multiSortParams) > 0) {
            $strEval = 'array_multisort('.join(',', $multiSortParams).', $this->tabData);';
            eval($strEval);
        }
        
    }

    /**
     * Crée une requête SQL contenant tous les filtres jqGrid
     *
     * @param array $tableauDeCorrespondance: entre nomJQgrid et champ SQL
     * @param object $filters
     * @param array $exclure: champs à exclure
     * @param array $champDate: champs date
     * @return String: le bout de requête SQL avec les conditions
     */
    public function getSqlFiltreJqGrid(
        $tableauCorrespondance, $filters=NULL, $exclure=array(), $champDate=array('date')
    ) {

        $filtrage = "";

        if($filters === NULL) {
            return $filtrage;
        }

        $filtreRules = new stdClass();
        $filtreRules->rules = NULL;

        foreach( $filters as $nom => $valeur) {
            $filtreRules->$nom = $valeur;
        }

        $count = count($filtreRules->rules);

        for($idxFiltre = 0; $idxFiltre < $count; $idxFiltre++) {

            $champ = $filtreRules->rules[$idxFiltre]->field;
            $valeurData = $filtreRules->rules[$idxFiltre]->data;
            $oper = $filtreRules->rules[$idxFiltre]->op;

            if( in_array($champ, $exclure) ) {
                continue;
            }

//            $valeur = str_replace('*', '%', $valeurData);
            $valeur1 = str_replace('*', '%', $valeurData);
            $valeur = $valeur1; # tmp à supprimer
            $valeur = str_replace('\'', '\'\'', $valeur1);

            if( $oper !== 'cn' ) {
                $valeur = $this->dateToTimestamp($champ, $valeur, $champDate, $oper);
            } else {
                if( in_array( $champ, $champDate) ) {
                    $champ = $champ.'Cn';
                }
            }

            switch($oper) {

                case 'cn':
                    $oper = sprintf(" LIKE LOWER('%s') ", '%'.strtolower($valeur).'%');
                break;

                case 'le':
                    $oper = sprintf(' <= %s', $valeur);
                break;

                case 'ge':
                    $oper = sprintf(' >= %s', $valeur);
                break;

                case 'le':
                    $oper = sprintf(' < %s', $valeur);
                break;

                case 'ge':
                    $oper = sprintf(' > %s', $valeur);
                break;

                case 'eq':
                case 'bw':
                    if(is_numeric($valeur)) {
                        $oper = sprintf(' = %s', $valeur);
                    } else {
                        $oper = sprintf(" = '%s'", strtolower($valeur));
                    }
                break;

                default:
                    // Emty default
                break;

            }
            if(is_numeric($valeur)) {
                $filtrage .= " AND " .$tableauCorrespondance[$champ] . $oper ;
            } else {
                $filtrage .= " AND LOWER(" .$tableauCorrespondance[$champ] . ")" . $oper ;
            }
        }

        return $filtrage;
    }


    /**
     * Créé une date au format timestamp
     *
     * @param String $date
     * @param String $operateurPourComparaison
     * @param Array $champDate
     * @return type
     */
    private function dateToTimestamp($champ, $date, $champDate, $operateurPourComparaison='') {

        if( in_array($champ, $champDate) ) {
            if (is_numeric($date)) {
                return $date;
            } else {
                $retour = $this->dateService->allFormatDateToTimestamp($date, $operateurPourComparaison);
                return $retour;
            }
        }
        return $date;
    }

    /**
     * Créé une date au format timestamp
     *
     * @param String $date
     * @param Array $champDate
     * @return type
     */
    private function timestampToDate($champ, $date, $champDate) {
        if( in_array($champ, $champDate) ) {
            $timestamp = $this->dateService->USFormatDateToTime($date);
            $date = $this->dateService->timeToAlerteFormatDate($timestamp);
        }
        return $date;
    }
    

    /**
     * Convertit le contenu d'un jqgrid au format CSV
     * @param type $colNames : tableau des entetes : correspond à  grid.getGridParam('colNames')
     * @param type $colModel : tableau de définition des champs : correspond à  grid.getGridParam('colModel')
     * @param type $gridDatas :tableau des données du jqGrid; correspond à grid.getRowData()
     * @param type $separateur Délimiteur de champs dans le CSV
     * @throws SinapsException
     * @throws Exception
     */
    public function convertGridToCsv($colNames, $colModel, 
                                                $gridDatas, $separateur = ';') {
		try {
					
			$tableauPourCSV = array(); 

			// Dans le tableau des données, 
			// la première ligne contient le tableau "colModel"
			// => on récupére les "name" pour récupérer les données et exclure les colonnes cachées
			$listeChampsATraiter = array();
			$listeChampsEntete = array();
			foreach($colModel as $definitionChamp) {
				if (isset($definitionChamp->hidden)) {
					if ($definitionChamp->hidden === 'true') {
						// Champ caché, on l'exclut fu scope
						array_shift($colNames); // on vire l'entete correspondante
						continue;
					}
				}
				$listeChampsATraiter[] = $definitionChamp->name;
				$listeChampsEntete[] = array_shift($colNames);
			}

			// On fusionne le tableau 'entetes et le tableau des données
			$tableauPourCSV[] = $listeChampsEntete;
			foreach ($gridDatas as $ligneDonnees) {
				$ligneAInserer = array();
				foreach ($listeChampsATraiter as $nomChamp) {
					$ligneAInserer[] = $ligneDonnees->$nomChamp;
				}
				$tableauPourCSV[] = $ligneAInserer;
			}

			// **************** Génération du CSV ****************
			// On parse le tableau et on écrit les
			$contenuCsv = '';
			foreach($tableauPourCSV as $ligneTableau) {
				$ligneCsv=sprintf('"%s"', implode('"'.$separateur.'"', array_values($ligneTableau)));
				$contenuCsv .= $ligneCsv."\n";
			}
			$retour = $this->jsonService->createResponse($contenuCsv);
			
		} catch(Exception $e) {
			$retour = JsonService::createErrorResponse(500, $e->getMessage());
		}
        return $retour;
    }
    
}
