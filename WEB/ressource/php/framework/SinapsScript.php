<?php
/**
 * Classe de base pour les scripts.
 *
 * Fonctions à redéfinir:
 *   - configure: pour déclarer les options et les arguments
 *   - performRun: la méthode réellement executée
 *
 *   - beforeRun: pour faire des controles avant l'execution
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

use \models\configuration\Application;

abstract class SinapsScript {
    /**
     * Le nom du script.
     *
     * @var string
     */
    protected $nom;
    /**
     * Texte de description du script.
     *
     * @var string
     */
    protected $description;
    /**
     * La classe de log à utiliser.
     *
     * @var string
     */
    protected $loggerName = NULL;
    /**
     * Le logger.
     *
     * @var Log2
     */
    protected $logger = NULL;
    /**
     * Le nombre de cas d'utilisation (parametres differents) du script.
     *
     * @var integer
     */
    protected $nbUsagesPossibles = 0;
    /**
     * La définition des valeurs et arguments du script (pour chaque cas d'utilisation).
     *
     * Définition=
     * array("nom", "flags", "description")
     *
     * @var array<int,array<definitions>>
     */
    protected $optionsDefinition = array();
    /**
     * Les valeurs passées au script.
     *
     * @var stdClass
     */
    protected $options;
    /**
     * Le namespace des modeles.
     *
     * @var string
     */
    static $modelsNamespace = "\\";
    /**
     * Le nom du log en paramètre.
     *
     * @var string
     */
    protected $nomLogger;
    /**
     * Le string du log en paramètre.
     *
     * @var string
     */
    protected $loggerString;
    /**
     * évenement lié
     *
     * @var String
     */
    protected $evenement;
    /**
     * Commande passé en console
     *
     * @var String
     */
    protected $cmdLineArgs;
    /**
     * L'application sur laquelle on fait un traitement
     *
     * @var String
     */
    protected $applicationConcernee = 0;

    const OBLIGATOIRE = 1;
    const FACULTATIF = 2;
    const FILE_TYPE = 4;
    const EXTRA_ACTION = 8;
    const VALUE_NONE = 16;
    const EST_PARAMETRE = 32;
    const DIR_TYPE = 64;

    /**
     * A surcharger: déclaration des parametres du script
     */
    abstract protected function configure();
    /**
     * A surcharger: execution du script
     */
    abstract protected function performRun();

    /**
     * Peut être surchargé: contrôles après lecture des paramètres et avant execution
     */
    protected function beforeRun() {
    }


    /**
     * Initialise l'application et enregistre le logger
     *
     * @param string $configDir    repertoire contenant le sinaps.ini
     * @param string $nomLogger    nom du logger
     * @param string $loggerString identifiant du logger dans sinaps.ini
     */
    public function __construct($configDir, $nomLogger, $loggerString) {

        $this->nomLogger = $nomLogger;
        $this->loggerString = $loggerString;

        SinapsApp::initialise($configDir, static::$modelsNamespace);

        SinapsApp::registerLogger($nomLogger, $loggerString);

        $this->logger = SinapsApp::make($nomLogger);

        $this->optionsDefinition[0] = array();
        $this->argsDefinition[0] = array();
        $this->configure();

        $this->addOption(
            "help",
            SinapsScript::FACULTATIF | SinapsScript::VALUE_NONE,
            "Affiche le USAGE"
        );
        $this->addOption(
            "DEBUG",
            SinapsScript::FACULTATIF | SinapsScript::VALUE_NONE,
            "Affiche les logs de niveau DEBUG"
        );

        if ($this->loggerName) {
            $this->logger = SinapsApp::make($this->loggerName);
        }

        $this->options = new stdClass();
    }

    /**
     * Traite le retour en mode help
     */
    private function HasModeHelp(&$cmdLineArgs) {

        // Pour chaque logger registrable on le passe en mode HTML
        // Cas de l'utilisation de plusieurs logger au sein du même script
        $debugPos = array_search("--help", $cmdLineArgs);
        if( $debugPos !== FALSE ) {
            $this->usage("");
        }
    }

    /**
     * Traite le retour en mode DEBUG
     */
    private function HasModeDebug(&$cmdLineArgs) {

        // Pour chaque logger registrable on le passe en mode HTML
        // Cas de l'utilisation de plusieurs logger au sein du même script
        $debugPos = array_search("--DEBUG", $cmdLineArgs);
        if( $debugPos !== FALSE ) {
            foreach( SinapsApp::$config as $key => $valeur) {
                if(preg_match('/^log\.\w+\.niveau$/', $key)) {
                    SinapsApp::$config[$key] = 0;
                }
            }
            unset($cmdLineArgs[$debugPos]);
            SinapsApp::registerLogger($this->nomLogger, $this->loggerString);
            $this->logger = SinapsApp::make($this->nomLogger);
        }
    }

    /**
     * Récupère les paramètres et lance l'execution
     *
     * @param boolean $useTransaction TRUE si on veut un rollback en cas de problème
     */
    public function run($useTransaction=TRUE) {
        $this->checkOptions();
        $this->beforeRun();

        try {

            $this->demarrerEvenement();

            if ($useTransaction) {
                OrmQuery::beginTransaction();
                $this->logger->addDebug("Transaction commencée.");
            }

            $this->performRun();

            if ($useTransaction) {
                OrmQuery::commit();
                $this->logger->addDebug("Transaction commitée.");
            }
            $this->terminerEvenement();

       } catch(Exception $e) {
            $this->logger->addCritical($e->getMessage());

            if ($useTransaction) {
                OrmQuery::rollback();
                $this->logger->addDebug("Restauration de l'état précédent effectué.");
            }
            $this->terminerEvenement($e->getMessage());

            $this->logger->addDebug($e->getTraceAsString());
            $exitCode = $e->getCode();
            if (!$exitCode) {
                $exitCode = 1;
            }
            exit($exitCode);
        }
    }

    private function demarrerEvenement() {
        if($this->evenement) {
            $classEvenement = static::$modelsNamespace . "Evenement";
            $this->evenement = $classEvenement::create($this->evenement, $this->cmdLineArgs);
            $this->evenement->demarrer();

        }
    }

    private function terminerEvenement($erreur=NULL) {
        if($this->evenement) {
            if($erreur) {
                $this->evenement->terminerScriptAvecErreur($erreur, $this->applicationConcernee);
            } else {
                $this->evenement->terminerScript($this->applicationConcernee);
            }
        }
    }

    /**
     * Lit et valide les paramètres du script
     *
     * @param integer $noAlternative La version des paramètres du script que l'on essaie de valider
     */
    protected function checkOptions($noAlternative=0) {
        global $argv;

        $cmdLineArgs = $argv;
        $this->cmdLineArgs = join(' ', $cmdLineArgs);

        // Supprssion du non du script
        array_shift($cmdLineArgs);

        $this->HasModeHelp($cmdLineArgs);
        $this->HasModeDebug($cmdLineArgs);

        // Lecture des '--'
        foreach($this->getOptions($noAlternative) as $definition) {
            $nomOption = $definition["nom"];

            $this->options->$nomOption = NULL;

            $position = array_search("--$nomOption", $cmdLineArgs);
            if ($position !== FALSE) {
                if ($definition["flags"] & static::VALUE_NONE) {
                    $this->options->$nomOption = TRUE;

                } else {
                    if (!array_key_exists($position + 1, $cmdLineArgs)) {
                        $this->usage(
                            SinapsApp::getErrorMsg("VALEUR_OBLIGATOIRE_POUR_PARAMETRE", $nomOption)
                        );
                    }
                    $this->options->$nomOption = $cmdLineArgs[$position + 1];
                    unset($cmdLineArgs[$position + 1]);
                }
                unset($cmdLineArgs[$position]);
            }
        }

        // Lecture des paramètres
        $arguments = $this->getArguments($noAlternative);

        foreach($arguments as $argument) {
            $nomArg = $argument["nom"];
            $this->options->$nomArg = NULL;
        }

        foreach($cmdLineArgs as $arg) {
            $argument = array_shift($arguments);
            if (!$argument) {
                $this->tryAlternativeSuivante(
                    $noAlternative,
                    SinapsApp::getErrorMsg("TROP_DE_PARAMETRES", $arg)
                );
                // Ca ne matche pas cette alternative, on a testé une fois la suivante, ça suffit.
                return;
            }

            $nomArg = $argument["nom"];
            $this->options->$nomArg = $arg;
        }

        // Vérification que les paramètres obligatoires ont été fournis
        foreach($this->optionsDefinition[$noAlternative] as $definition) {
            $nomOption = $definition["nom"];

            if ($this->options->$nomOption !== NULL ) {
                if ($definition["flags"] & static::FILE_TYPE) {
                    $ok = $this->checkFile($this->options->$nomOption);
                    if ($ok !== TRUE) {
                        $this->tryAlternativeSuivante($noAlternative, $ok);
                        return;
                    }
                }
                if ($definition["flags"] & static::DIR_TYPE) {
                    $ok = $this->checkDir($this->options->$nomOption);
                    if ($ok !== TRUE) {
                        $this->tryAlternativeSuivante($noAlternative, $ok);
                        return;
                    }
                }
            }

            if (($definition["flags"] & static::OBLIGATOIRE) &&
                $this->options->$nomOption === NULL) {
                $this->tryAlternativeSuivante(
                    $noAlternative,
                    SinapsApp::getErrorMsg("PARAM_OBLIGATOIRE", $nomOption)
                );
                return;
            }
        }

        // postAction
        foreach($this->optionsDefinition[$noAlternative] as $definition) {
            $nomOption = $definition["nom"];

            if ($this->options->$nomOption !== NULL) {
                if ($definition["flags"] & static::EXTRA_ACTION) {
                    $nomMethode = $nomOption . "Extra";
                    $this->$nomMethode();
                }
            }
        }
    }

    /**
     * Essaie d'appeler l'alternative suivante si on n'a pas matché les paramètres pour celle en cours
     *
     * @param integer $noAlternative  le n° d'alternative
     * @param string  $erreurDetectee le message à afficher si il n'y a plus d'alternative possible
     */
    protected function tryAlternativeSuivante($noAlternative, $erreurDetectee) {
        // On essaie avec une autre forme d'invocation du script
        $this->options = new stdClass();

        if ($noAlternative < $this->nbUsagesPossibles) {
            $this->checkOptions($noAlternative + 1);
        } else {
            $this->usage($erreurDetectee);
        }
    }

    /**
     * Vérifie que le paramètre est bien un fichier existant
     *
     * @param string $file le fichier à vérifier
     * @return Mixed       TRUE ou un msg d'erreur en cas de problème
     */
    protected function checkFile($file) {
        if (!file_exists($file)) {
            return SinapsApp::getErrorMsg("FICHIER_MANQUANT", $file);
        }
        if(filesize($file) == 0) {
            return SinapsApp::getErrorMsg("FICHIER_VIDE", $file);
        }

        return TRUE;
    }

    /**
     * Vérifie que le paramètre est bien un dossier existant
     *
     * @param string $dir le dossier à vérifier
     * @return Mixed       TRUE ou un msg d'erreur en cas de problème
     */
    protected function checkDir($dir) {
        if (!is_dir($dir)) {
            return SinapsApp::getErrorMsg("REPERTOIRE_INEXISTANT", $dir);
        }

        return TRUE;
    }

    /**
     * Appelé par le dev depuis configure pour commencer une nouvelle alternative.
     *
     * @return SinapsScript this
     */
    public function startAlternative() {
        $this->nbUsagesPossibles++;
        $this->optionsDefinition[$this->nbUsagesPossibles] = array();

        return $this;
    }

    /**
     * Affiche à l'utilisateur du script le ou les usages possibles
     *
     * @param string $msg le message d'erreur à afficher en plus du usage
     */
    protected function usage($msg) {

        if ($msg) {
            print "\nUne erreur s'est produite: \033[0;31m  $msg\033[0m\n\n";
        }

        $msgOptionsCommunes = "";

        print "\033[1mDescription:\033[0m\n" . $this->description . "\n\n";

        for($i = 0; $i <= $this->nbUsagesPossibles; $i++) {
            print "\033[1mUSAGE: php ". basename($this->nom) . " " . $this->paramsToTexte($i) . "\033[0m\n";

            // Arguments
            $arguments = $this->getArguments($i);
            if (count($arguments) > 0)
                print "Arguments:\n";

            foreach($arguments as $nomArg => $definition) {
                printf("\t%-20s: " . $definition["documentation"] . "\n", $nomArg);
            }

            // Options obligatoires
            $obligatoires = array_filter(
                $this->optionsDefinition[$i],
                function ($element) {
                    return ($element["flags"] & SinapsScript::OBLIGATOIRE) &&
                           !($element["flags"] & SinapsScript::EST_PARAMETRE);
                }
            );

            if (count($obligatoires) > 0) {
                print "Paramètres obligatoires:\n";
                foreach($obligatoires as $nomOption => $definition) {
                    printf("\t--%-20s: " . $definition["documentation"] . "\n", $nomOption);
                }
            }

            // Options facultatives
            $facultatifs = array_filter(
                $this->optionsDefinition[$i],
                function ($element) {
                    return ($element["flags"] & SinapsScript::FACULTATIF) &&
                           !($element["flags"] & SinapsScript::EST_PARAMETRE);
                }
            );

            if (count($facultatifs) > 0) {
                print "Paramètres optionnels:\n";
                foreach ($facultatifs as $nomOption => $definition) {
                    // Pour HTML et DEBUG on les affichera à la toute fin
                    if( $nomOption === "DEBUG" || $nomOption === "help") {
                        $msgOptionsCommunes[$nomOption] = "\t--%-20s: " . $definition["documentation"] . "\n";
                        continue;
                    }

                    if ($definition["flags"] & static::FACULTATIF) {
                        printf("\t--%-20s: " . $definition["documentation"] . "\n", $nomOption);
                    }
                }
            }

            print "\n";
        }

        print "Paramètres optionnels commun à tous les scripts:\n";
        foreach($msgOptionsCommunes as $nomOption => $msgOC) {
            printf($msgOC, $nomOption);
        }

        exit(1);
    }

    /**
     * Appelé par usage convertit les parametres en texte présentable à l'utilisateur
     *
     * @param integer $i L'alternative concernée
     * @return string le texte formaté
     */
    protected function paramsToTexte($i) {
        $result = array();
        foreach($this->optionsDefinition[$i] as $nomOption => $definition) {
            if( $nomOption === "DEBUG" || $nomOption === "help") {
                continue;
            }

            if ($definition["flags"] & static::EST_PARAMETRE)
                $option = "<$nomOption>";
            else {
                $option = "--$nomOption";

                if (! ($definition["flags"] & static::VALUE_NONE)) {
                    $option .= " <$nomOption>";
                }
            }
            if ($definition["flags"] & static::FACULTATIF) {
                $option = "[$option]";
            }

            $result[] = $option;
        }

        return implode(" ", $result);
    }

    /**
     * Défini le nom du script
     *
     * @param string $nom le nom
     */
    protected function setName($nom) {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Défini la description du script
     *
     * @param string $description la description
     */
    protected function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * Défini l'évenement lié au script
     *
     * @param string $evenement l'événement
     */
    protected function setEvenement($evenement) {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Défini l'application concernée
     */

    protected function setApplicationConcernee($application) {
        $this->applicationConcernee = $application;
    }

    /**
     * Normalement appelé depuis configure, ajoute une option à la ligne de commande
     *
     * @param string  $nomOption nom de l'option (--$nom)
     * @param integer $flags     caracteristiques de l'option (OBLIGATOIRE ...)
     * @param string  $doc       doc à afficher à l'utilisateur
     */
    protected function addOption($nomOption, $flags, $doc) {
        $this->optionsDefinition[$this->nbUsagesPossibles][$nomOption] = array(
            "nom" => $nomOption,
            "flags" => $flags,
            "documentation" => $doc
        );

        return $this;
    }

    /**
     * Normalement appelé depuis configure, ajoute un argument à la ligne de commande
     *
     * @param string  $nomOption nom de l'argument
     * @param integer $flags     caracteristiques de l'option (OBLIGATOIRE ...)
     * @param string  $doc       doc à afficher à l'utilisateur
     */
    protected function addArgument($nomArg, $flags, $documentation) {
        $this->optionsDefinition[$this->nbUsagesPossibles][$nomArg] = array(
            "nom" => $nomArg,
            "flags" => $flags + static::EST_PARAMETRE,
            "documentation" => $documentation
        );

        return $this;
    }

    /**
     * Retourne tous les définitions d'arguments de cette alternative
     *
     * @param integer $noAlternative alternative
     * @return array les arguments
     */
    protected function getArguments($noAlternative) {
        $result = array_filter(
            $this->optionsDefinition[$noAlternative],
            function ($definition) {
                return $definition["flags"] & SinapsScript::EST_PARAMETRE;
            }
        );

        return $result;
    }

    /**
     * Retourne toutes les définitions d'options de cette alternative
     *
     * @param integer $noAlternative alternative
     * @return array les options
     */
    protected function getOptions($noAlternative) {
        $result = array_filter(
            $this->optionsDefinition[$noAlternative],
            function ($definition) {
                return !($definition["flags"] & SinapsScript::EST_PARAMETRE);
            }
        );

        return $result;
    }

    /**
     * Retourne tous les options/args obligatoire
     *
     * @param mixed $source Si entier l'alternative, si tableau, le tableau à filtrer
     * @return array
     */
    protected function getObligatoire($source=0) {
        if (!is_array($source)) {
            $source = $this->optionsDefinition[$source];
        }

        $result = array_filter(
            $source,
            function ($definition) {
                return $definition["flags"] & SinapsScript::OBLIGATOIRE;
            }
        );

        return $result;
    }

    /**
     * Retourne tous les options/args facultatifs
     *
     * @param mixed $source Si entier l'alternative, si tableau, le tableau à filtrer
     * @return array
     */
    protected function getFacultatif($source=0) {
        if (!is_array($source)) {
            $source = $this->optionsDefinition[$source];
        }

        $result = array_filter(
            $source,
            function ($definition) {
                return $definition["flags"] & SinapsScript::FACULTATIF;
            }
        );

        return $result;
    }

    /**
     * Recherche l'application de nom options->application et la place dans application.
     */
    protected function applicationExtra() {
        $class = static::$modelsNamespace . "Application";
        $this->application = $class::where("nom", $this->options->application)->first();

        if (!$this->application) {
            $this->usage(SinapsApp::getErrorMsg("APPLICATION_N_EXISTE_PAS", $this->options->application));
        }
    }

    /**
     * Recherche l'application de nom options->applicationConcernee et la place dans applicationConcernee.
     */
    protected function applicationConcerneeExtra() {
        $this->setApplicationConcernee($this->options->applicationConcernee);
    }

}
