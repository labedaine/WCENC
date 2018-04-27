<?php
/**
 * Classe masquant l'implémentation réelle du cache.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class Cache {
    protected $cache = array();
    protected $initFonction = NULL;

    public function __construct(Closure $fonctionDInitialisation) {
        $this->initFonction = $fonctionDInitialisation;
    }

    public function get() {
        $key = implode('.', func_get_args());
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = call_user_func(
                $this->initFonction,
                func_get_args()
            );
        }

        return $this->cache[$key];
    }
}