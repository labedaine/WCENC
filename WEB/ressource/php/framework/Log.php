<?php
/**
 * Class générique de log.
 * 
 * Gère les écritures dans un fichier ou sur la sortie standard
 * A l'initialisation, on lui passe :
 *  - le chemin complet du fichier.  Si celui-ci est null, on écrira sur la sortie standard
 *  - un flag qui indique si on ouvre on non un pointeur sur le fichier
 *
 * Utilisation :
 *  - par instanciation directe
 * exemple : $objLog  new Log(); // écrit directement sur la sortie standard
 * exemple : $objLog  new Log('/var/log/monFichier.log'); // écrit ves le fichier "monFichier.log"
 * - en rattachant l'instance de la classe Log à une propriété de classe (avec SinapsApp::make)
 *
 * Les sévérités du format RSP 3
 *  - Emergency – the system is unusable
 *  - Alert – immediate action is required
 *  - Critical – critical conditions
 *  - Error – errors that do not require immediate attention but should be monitored
 *  - Warning – unusual or undesirable occurrences that are not errors
 *  - Notice – normal but significant events
 *  - Info – interesting events
 *  - Debug – detailed information for debugging purposes
 *
 * PHP version 5
 *
 * @author esi-lyon <esi-lyon@dgfip.finances.gouv.fr>
 */

class Log {
    public $fd = NULL; // Pointeur d'ouverture de fichier (si utilisé)
    public $file = NULL;
    public $format = "[%s] [%s] %s\n"; // ou  "[%s] [%-9s] %s\n";
    public $contexte = NULL; // si la colonne contexte est renseignée, on l'ajoute au format :
    // On aura alors "[%s] [%s] [%s] %s\n"
    public $isDebug = FALSE;

    // Constantes de sévérité
    const SEVEMERGENCY = 'emergency';
    const SEVALERT = 'alert';
    const SEVCRITICAL = 'critical';
    const SEVERROR = 'error';
    const SEVWARNING = 'warning';
    const SEVNOTICE = 'notice';
    const SEVINFO = 'info';
    const SEVDEBUG = 'debug';

    /**
     * Constructeur de la class
     * @param string $cheminFichier  @TODO
     * @param bool   $toujoursOuvert @TODO
     * @param string $format         @TODO
     * @param bool   $insertDebug    si vrai, insère les "Debug" dans le fichier
     * @throws Exception @TODO.
     */
    public function __construct($cheminFichier=NULL, $isDebug=FALSE, $toujoursOuvert=FALSE, $format=NULL) {
        /*
            Gestion du format (le laisser en premier)
        */

        if ( $format)
            $this->format = $format;

        $this->isDebug = $isDebug;

        // Si le chemin de fichier est renseigné
        if ("$cheminFichier" !== '' ) {
            $this->file = $cheminFichier;
            if (! is_file("$cheminFichier")) {
                // Création du fichier
                $this->ecrireDansLog(self::SEVINFO, 'Initialisation du fichier');
            }
            if (! is_file("$cheminFichier"))
                throw new Exception("ERREUR: impossible d'ouvrir le fichier de log $cheminFichier");

            /*
                Si le paramètre "toujoursOuvert" est passé
                on maintient le fichier
            */

            if ($toujoursOuvert) {
                $this->open();
            }
        }
    }

    /**
     * Destructeur de la classe.
     *
     * Si le pointeur existe, on ferme le fichier
     */
    public function __destruct() {
        if( $this->fd )
            $this->close();
    }

    /**
     * TODO.
     * @param string $level @TODO
     * @param string $texte @TODO
     */
    private function ecrireDansLog($level=self::SEVINFO, $data=NULL) {
        if ($level === NULL) {
            // Si $level est null, on affiche le texte en ligne sans formatage
             $out = vsprintf("%s\n", $data);
        } elseif ($this->contexte === NULL) {
             $out = vsprintf("[%s] [%s] %s\n", array(date('Y-m-d H:i:s'), $level, $data));
        } else {
            if( is_array($this->contexte) )
                $this->contexte = join('] [', $this->contexte);
            $out = vsprintf("[%s] [%s] [%s] %s\n", array(date('Y-m-d H:i:s'), $level, $this->contexte, $data));
        }

        // Si le pointeur est ouvert, on écrit dans le ficheir
        if( $this->fd ) {// le pointeur sur le fichier est valide
            fwrite($this->fd, $out); // Ecrit dans lefichier avec le pointeur de fichier
        } elseif ($this->file) {
            file_put_contents($this->file, $out, FILE_APPEND);
        } else {// Le pointeur sur le fichier n'est pas vailde
            print $out; // Ecrit sur la sortie standard
        }

    }

    /**
     * Ajoute un dump dans le log
     * @param string $var_dump @TODO
     */
    private function dump($varDump) {
        $var = print_r($varDump, TRUE);
        $this->ecrireDansLog(NULL, $var);
    }

    /**
     * Ouvre le fichier de log et crée un pointeur sur ce fichiers
     */
    private function open() {
        $fd = @fopen($this->file, 'a');
        if(!$fd)
            throw new Exception(__METHOD__.': Impossible d\'ouvrir '.$this->file);

        $this->fd = $fd;
    }

    /**
     * Ferme le pointeur sur le fichier log du moteur
     */
    private function close() {
        if( $this->fd )
            @fclose($this->fd);
    }

    /**
     * Ajoute un message de sévérité "emergency"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addEmergency($texte, &$varDump=NULL) {
        // Niveau Emergency
        $this->ecrireDansLog(self::SEVEMERGENCY, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "alert"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addAlert($texte, &$varDump=NULL) {
        // Niveau Alert
        $this->ecrireDansLog(self::SEVALERT, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "critical"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addCritical($texte, &$varDump=NULL) {
        // Niveau Critical
        $this->ecrireDansLog(self::SEVCRITICAL, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "error"
     *
     * @param string  $texte    Texte à afficher
     * @param boolean $var_dump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addError($texte, &$varDump=NULL) {
        // Niveau Error
        $this->ecrireDansLog(self::SEVERROR, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "warning"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addWarning($texte, &$varDump=NULL) {
        // Niveau Warning
        $this->ecrireDansLog(self::SEVWARNING, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "notice"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addNotice($texte, &$varDump=NULL) {
        // Niveau Notice
        $this->ecrireDansLog(self::SEVNOTICE, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de sévérité "info"
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addInfo($texte, &$varDump=NULL) {
        // Niveau Info
        $this->ecrireDansLog(self::SEVINFO, $texte);
        if ($varDump !== NULL) {
            $this->dump($varDump);
        }
    }

    /**
     * Ajoute un message de debug
     *
     * @param string  $texte   Texte à afficher
     * @param boolean $varDump Si renseigné, fait un print_r à la suite sans formatage
     */
    public function addDebug($texte, $varDump=NULL) {
        if ($this->isDebug) {
            // Niveau Debug : permet d'insèrer un print_r d'une variable
            $this->ecrireDansLog(self::SEVDEBUG, $texte);
            if ($varDump !== NULL) {
                $this->dump($varDump);
            }
        }
    }

}
