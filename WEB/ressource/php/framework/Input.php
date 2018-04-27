<?php
/**
 * Classe gérant les paramètres passés en paramètre.
 * 
 * Capable de comprendre les paramètres GET et POST
 * mais également le json contenu dans un paramètre json=
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Input {
    // @var boolean $jsonParse: le json si on a déjà parsé le champ json sinon null
    protected $jsonParse = NULL;

    static protected $instance = NULL;

    /**
     * Retourne la valeur du paramètre demandé
     * 
     * @param String $name         le nom du paramètre
     * @param String $defaultValue la valeur par défaut si on ne trouve pas le paramètre
     * @throws SinapsException Si on n'arrive pas à trouver le paramètre 
     * et que l'on a pas de valeur par défaut.
     * @return String: la valeur
     */
    static public function get($name, $defaultValue=NULL) {
        static::init();
        $retour = static::$instance->getOnInstance($name, $defaultValue);
        return $retour;
    }

    /**
     * vide les données statiques (nécessaire pour les tests)
     */
    public static function reset() {
        static::init();
        static::$instance->resetOnInstance();
    }

    static public function init($implementation=NULL) {
        if ( $implementation !== NULL) {
            static::$instance = $implementation;
        } else if ( static::$instance === NULL) {
            self::$instance = new Input();
        }
    }

    static public function set($name, $valeur) {
        static::init();
        static::$instance->setOnInstance($name, $valeur);
    }


    /**
     * Retourne la valeur du paramètre demandé
     * 
     * @param String $name         le nom du paramètre
     * @param String $defaultValue la valeur par défaut si on ne trouve pas le paramètre
     * @throws SinapsException Si on n'arrive pas à trouver le paramètre 
     * et que l'on a pas de valeur par défaut.
     * @return String: la valeur
     */
    public function getOnInstance($name, $defaultValue=NULL) {
        if (isset($_REQUEST["$name"])) {
            return $_REQUEST["$name"];
        } 

        $result = $this->findInFiles($name);
        if ( $result === NULL) {
            $result = $this->findInJson($name);
            if ( $result === NULL) {
                $result = $defaultValue;
            }
        }

        if ($result === NULL) {
            throw new SinapsException("Paramètre $name obligatoire et non fourni");
        }

        return $result;
    }

    /**
     * vide les données statiques (nécessaire pour les tests)
     */
    public static function resetOnInstance() {
        $this->jsonParse = NULL;
    }

    /**
     * Recherche le paramètre dans le champs json
     * 
     * @param String $name: le paramètre
     * @return NULL ou la valeur
     */
    protected function findInJson($name) {
        if ( $this->jsonParse === NULL &
              isset($_REQUEST["json"])) {
            $this->jsonParse = json_decode($_REQUEST["json"]);
        }

        if ( $this->jsonParse &&
             array_key_exists($name, $this->jsonParse)) {
            return $this->jsonParse->$name;
        }

        return NULL;
    }

    protected function findInFiles($name) {
        if (!isset($_FILES[$name]))
            return NULL;
        // Gestion des erreurs durant le téléchargement des fichiers
        if (isset($_FILES[$name]['error']) && $_FILES[$name]['error'] !== 0)
            throw new SinapsException("Erreur de file upload: n° erreur:" . $_FILES[$name]['error']);
        if (!isset($_FILES[$name]['tmp_name']) || !isset($_FILES[$name]['name']))
            return NULL;
        return array(
            "localName" => $_FILES[$name]['tmp_name'],
            "originalName" => $_FILES[$name]['name']
        );
    }

    protected function setOnInstance($nom, $valeur) {
        return;
    }
}
