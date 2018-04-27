<?php
/**
 * Classe de base pour les controllers.
 * 
 * Les appels sont faits directement par le framework à partir de @see Route
 * 
 * Sais appliquer des filtres avant l'appel d'une action
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class BaseController {
    /**
     * Nom des filtres à appliquer avant l'appel à l'action.
     *
     * @var array<String>
     */
    private $beforeFilters = array();
 
    /**
     * Ajout un filtre
     * 
     * @param String $filterName le nom du filtre (il doit exister dans l'injecteur de dépendance 
     */
    public function beforeFilter($filterName) {
        $this->beforeFilters[] = $filterName;
    }

    /**
     * Appele une action en appliquant les filtres
     * 
     * @param string $action  l'action à appeler
     * @param array  $matches le résultat de la regexp de sélection @see Route#resolve
     */
    public function invoke($action, array $matches=array()) {
        try {
            $this->applyBeforeFilter();
            $retour = $this->$action($matches);
            return $retour;
        } catch( SinapsException $e) {
            $retour = $this->handleException($e);
            return $retour;
        }
    }

    /**
     * Applique les filtres pré action
     */
    public function applyBeforeFilter() {
        foreach( $this->beforeFilters as $filterName) {
            $this->applyFilter($filterName);
        }
    }

    /**
     * Applique immédiatement le filtre passé en paramètre
     * 
     * @param String $filterName:le nom du filtre (doit exister dans l'injecteur de dépendance)
     */
    public function applyFilter($filterName) {
        $filter = App::make("filter:$filterName");
        $filter->apply();
    }

    /**
     * Appelé lorsqu'une SinapsException est déclenchée
     * 
     * A surcharger par les controllers fils
     * 
     * @param SinapsException $e l'exception qui a été levée
     */
    protected function handleException(SinapsException $exception) {
        App::Abort($exception->getCode(), $exception->getMessage());
    }
}
