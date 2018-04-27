<?php
/**
 * Classe gérant les cookies.
 * 
 * Permet de créer, récupérer et supprimer un cookie
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Cookie {
    /**
     * Crée un cookie eternel (30j en fait) de nom $name et de valeur $value.
     *
     * A noter que le cookie est valide pour tout le domaine
     * 
     * @param string $name  le nom du cookie
     * @param string $value la valeur du cookie
     */
    static function forever($name, $value) {
        setcookie($name, $value, strtotime('+30 days'), "/");
    }

    /**
     * Crée un cookie eternel (30j en fait) de nom $name et de valeur $value.
     *
     * A noter que le cookie est valide pour tout le domaine
     * 
     * @param string $name  le nom du cookie
     * @param string $value la valeur du cookie
    */
    static function session($name, $value) {
        setcookie($name, $value, 0, "/");
    }

    /**
     * Récupère la valeur du cookie de nom $name
     * 
     * @param string $name nom du cookie
     * @return string le contenu du cookie
     */
    static function get($name) {
        return $_COOKIE[$name];
    }

    /**
     * Retourne true si le cookie $name existe
     * 
     * @param string $name le nom du cookie
     * @return boolean TRUE si le cookie existe
     */
    static function has($name) {
        return array_key_exists($name, $_COOKIE);
    }

    /**
     * Supprime le cookie $name 
     * 
     * @param string $name nom du cookie
     */
    static function delete($name) {
        setcookie($name, "", strtotime('-30 days'), "/");
    }
}
