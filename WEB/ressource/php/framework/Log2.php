<?php
/**
 *  Permet de gérer les logs.
 *
 *  Est normalement injecté dans l'application par SinapsApp::singleton("Logger", )
 *
 *  La configuration se fait à partir des fichiers .ini avec les clef suivante:
 *      log.$nomLogger.niveau
 *      log.$nomLogger.formats
 *      log.$nomLogger.writers
 *
 *  Niveau: Int: spécifie le niveau minimal pour que les logs sont passés aux writers.
 *  Formats: nomClasseFormat1(!options0(!options1)?)?(,nomClasseFormat2 ...)
 *           enrichit le message d'informations complémentaires
 *  Writers: nomClasseWriter1(!options0(!options1)?)?(,nomClasseWriter2 ...)
 *           sauvegarde/affiche les messages
 *
 * http://venezia.appli.dgfip/plugins/mediawiki/wiki/sinaps/index.php/Framework:_Logs
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/log_writers/LogWriter.php";

class Log2 {
    const DEBUG_LEVEL = 0;
    const INFO_LEVEL = 1;
    const WARNING_LEVEL = 2;
    const ERROR_LEVEL = 3;
    const CRITICAL_LEVEL = 4;

    const DEBUG_STRING = "debug";
    const INFO_STRING = "info";
    const WARNING_STRING = "warning";
    const ERROR_STRING = "error";
    const CRITICAL_STRING = "CRITICAL";

    protected $niveauLog;
    protected $formats = array();
    protected $writers = array();

    public $contexte = NULL;

    /**
     * lit la configuration
     *
     * @param string $nom: le nom du logger
     */
    public function __construct($nom) {
        $this->lireConfiguration($nom);
    }

    /**
     * Fonction chargée d'interpréter la configuration du log.
     *
     * Lit 3 éléments:
     *   le niveau de log "log.$nom.niveau" de type numérique
     *   les formats à appliquer (ils sont appliqués dans l'ordre)
     *   les classes de sortie des logs
     *
     * @param String $nom le nom du logger
     */
    protected function lireConfiguration($nom) {
        // Recherche des clef log.$nom.niveau
        $this->niveauLog = static::ERROR_LEVEL;
        $config = SinapsApp::getConfigValue("log.$nom.niveau");
        if ($config !== NULL) {
            $this->niveauLog = $config;
        }

        // Recherche des clef log.$nom.formats
        $config = SinapsApp::getConfigValue("log.$nom.formats");
        if ($config !== NULL) {
            $formats = explode(",", $config);
            foreach( $formats as $format) {
                $parametres = explode("!", $format);

                $nomClasseFormat = array_shift($parametres);

                include_once __DIR__ . "/log_formats/$nomClasseFormat.php";
                $this->formats[] = new $nomClasseFormat( $nom, $parametres);
            }
        }

        // Recherche des clef log.$nom.writters
        $config = SinapsApp::getConfigValue("log.$nom.writers");
        if ($config !== NULL) {
            $writers = explode(",", $config);
            foreach( $writers as $writer) {
                $parametres = explode("!", $writer);

                $nomClasseFormat = array_shift($parametres);

                include_once __DIR__ . "/log_writers/$nomClasseFormat.php";
                $this->writers[] = new $nomClasseFormat( $nom, $parametres);
            }
        }
    }

    /**
     * Ajoute un log de type debug
     * @param string $message: le message de log
     */
    public function addDebug($message) {
        if ( $this->niveauLog <= static::DEBUG_LEVEL) {
            $this->produireMessage("debug", $message);
        }
    }

    /**
     * Ajoute un log de type info
     * @param string $message: le message de log
     */
    public function addInfo($message) {
        if ( $this->niveauLog <= static::INFO_LEVEL) {
            $this->produireMessage("info", $message);
        }
    }

    /**
     * Ajoute un log de type warning
     * @param string $message: le message de log
     */
    public function addWarning($message) {
        if ( $this->niveauLog <= static::WARNING_LEVEL) {
            $this->produireMessage("warning", $message);
        }
    }

    /**
     * Ajoute un log de type error
     * @param string $message: le message de log
     */
    public function addError($message) {
        if ( $this->niveauLog <= static::ERROR_LEVEL) {
            $this->produireMessage("error", $message);
        }
    }

    /**
     * Ajoute un log de type error
     * @param string $message: le message de log
     */
    public function addCritical($message) {
        if ( $this->niveauLog <= static::CRITICAL_LEVEL) {
            $this->produireMessage("CRITICAL", $message);
        }
    }

    /**
     * Ajoute un writer à la liste des writers
     * @param LogWriter $handler: le writer
     */
    public function pushHandler(LogWriter $handler) {
        $this->writers[] = $handler;
    }

    /**
     * Ajoute un format à la liste des formats
     * @param LogFormat $processor: le format
     */
    public function pushProcessor(LogFormat $processor) {
        $this->formatters[] = $processor;
    }

    /**
     * Formate et écrit le log
     * @param string $niveau  niveau du log
     * @param string $message message de log
     */
    public function produireMessage($niveau, $message) {
        if(!is_null($this->contexte)) {
            if( is_array($this->contexte) )
                    $this->contexte = join('] [', $this->contexte);
            $message = vsprintf("[%s] %s", array($this->contexte, $message));
        }

        foreach( $this->formats as $format) {
            $message = $format->format($niveau, $message);
        }

        $this->sendMessageToWriters($niveau, $message);
    }

    /**
     * Signifie le début d'une étape qu'on souhaite suivre
     * @param string $nomEtape: nom de l'étape
     */
    public function debuterEtape($nomEtape="__DEFAULT", $message="") {
        $result = NULL;
        foreach( $this->formats as $format) {
            $res = $format->debuterEtape($nomEtape, $message);
            if ($res)
                $result = $res;
        }

        if ($result)
            $this->sendMessageToWriters(static::INFO_STRING, $result);
    }

    /**
     * Indique qu'une étape est terminée.
     *
     * Génère un log de niveau "info" contenant les informations de l'étape
     * @param string $message  message ajouté aux informations de l'étape (facultatif)
     * @param string $nomEtape nom de l'étape (facultatif)
     */
    public function finirEtape($message=NULL, $nomEtape=NULL) {
        foreach( $this->formats as $format) {
            $message = $format->finirEtape($message, $nomEtape);
        }

        $this->sendMessageToWriters(static::INFO_STRING, $message);
    }

    /**
     * Essaie de retourner la liste des logs ayant été générés pour ce niveau de log.
     *
     * Ne fonctionne qui si un des writer conserve l'historique des logs
     *
     * @param string $niveau: niveau de log
     * @return array|NULL array si un writer conserve l'historique, null sinon
     */
    public function dump($niveau) {
        foreach ($this->writers as $writer) {
            $result = $writer->dump($niveau);

            if ($result) {
                return $result;
            }
        }

        return array();
    }

    /**
     * Vide les logs précédents.
     *
     * @return array|NULL array si un writer conserve l'historique, null sinon
     */
    public function flush() {
        foreach ($this->writers as $writer) {
            $result = $writer->flush();

            if ($result) {
                return $result;
            }
        }

        return array();
    }

    /**
     * Appele tous les writers configurés pour stocker le message
     *
     * @param string $niveau  niveau de log
     * @param string $message message de log déjà complété par tous les formats
     */
    protected function sendMessageToWriters($niveau, $message) {
        foreach ($this->writers as $writer) {
            $writer->write($niveau, $message);
        }
    }
}
