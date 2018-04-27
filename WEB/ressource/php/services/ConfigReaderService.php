<?php
/**
 * Classe permetttant de lire la configuration SINAPS.
 *
 * Actuellement cherche le fichier sinaps.ini
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class ConfigReaderService {
    protected static $instance;

    /**
     * Lit la configuration
     *
     * Contient le fichier "sinaps.ini", et éventuellement le fichier Config.php
     *
     * @param string $cheminConfig Chemin du dossier Config
     * @return array<String => String>
     */

    public static function readConfig($cheminConfig) {
        if (!static::$instance) {
            static::$instance = new ConfigReaderService();
        }
        $retour = static::$instance->readConfigOninstance($cheminConfig);
        return $retour;
    }

    public static function init($instance=NULL) {
        static::$instance = $instance;
    }

    public static function deleteInstance() {
        static::$instance = NULL;
    }

    protected function readConfigOninstance($cheminConfig) {
        if (! is_dir($cheminConfig)) {
            throw new Exception(
                vsprintf(
                    'ERREUR %s: le dossier de configuration [%s] est introuvable.',
                    array(__FUNCTION__, $cheminConfig)
                )
            );
        }
       // Si le fichier Config.php existe on fait un require_once
        $cheminConfigPHP = $cheminConfig.'/Config.php';
       if (is_file($cheminConfigPHP)) {
           include_once $cheminConfigPHP;
       }
       // 2. Charge le .ini de /produits/sinaps/apps/commun
       $iniFileCommun = dirname(__FILE__).'/../config/sinaps.ini';
       $tabCommun = parse_ini_file($iniFileCommun);
        // 3. Charge le fichier INI passé en paramètre
        $iniFile = $cheminConfig . '/sinaps.ini';
        if (!is_file($iniFile)) {
            throw new Exception(
                vsprintf(
                    'ERREUR %s: le fichier ini [%s] est introuvable.',
                    array(__FUNCTION__, $iniFile)
                )
            );
        }
        $tabSpecifique = parse_ini_file($iniFile);
        $retour = array_merge($tabCommun, $tabSpecifique);
        return $retour;
    }

}
