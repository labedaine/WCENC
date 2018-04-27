<?php
/**
 * Service fournissant les fonctions de base pour accéder aux fichiers.
 *
  * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class FileService {
    // service d'accès au shell
    // @var SystemmService
    protected $systemService;

    /**
     * Initialise les services utilisés (D.I)
     */
    public function __construct() {
        $this->systemService = SinapsApp::make("SystemService");
    }

    public function fopen($file, $mode="r") {
        try {
            switch($mode) {
                case "w":
                case "wb":
                    if(file_exists($file) &&
                       !is_writable($file)) {
                        throw new SinapsException(
                            "Les droits ne sont pas suffisant pour écrire dans le fichier $file"
                        );
                    };
                    break;
                case "r":
                    if(!is_readable($file)) {
                        throw new SinapsException(
                            "Les droits ne sont pas suffisant pour lire fichier $file"
                        );
                    };
                    break;
                default:
                    // fix Coca : gestion obligatoire d'un cas par défaut pour switch
                    break;
            }


            $fileHandle = fopen($file, $mode);

            if ($fileHandle === FALSE) {
                throw new SinapsException(
                    "Impossible de créer le fichier $file"
                );
            }

            return $fileHandle;

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function fclose($fileHandle) {
        try {
            if ($fileHandle) {
                fclose($fileHandle);
            }
        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function file_get_contents($nomFichier) {
        try {
            return file_get_contents($nomFichier);
        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function file_put_contents($nomFichier, $contenu) {
        try {
            return file_put_contents($nomFichier, $contenu);
        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function file_exists($nomFichier) {
        try {
            return file_exists($nomFichier);
        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function fgetcsv($fileHandle, $max, $sep, $quote) {
        $resultat = NULL;
        if ($fileHandle) {
            $resultat = fgetcsv($fileHandle, $max, $sep, $quote);
        }

        return $resultat;
    }

    public function fputcsv($fileHandle, array $colonnes, $sep, $quote) {
        try {
            return fputcsv($fileHandle, $colonnes, $sep, $quote);

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function fwrite($fileHandle, $chaine) {
        try {
            $resultat = fwrite($fileHandle, $chaine);

            return $resultat;

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function feof($fileHandle) {
        try {
            $resultat = feof($fileHandle);

            return $resultat;
        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function fgets($handle) {
        try {
            $resultat = fgets($handle);

            return $resultat;

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function mkdir($path) {
        try {
            $resultat = mkdir($path);

            return $resultat;

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function rename($from, $to) {
        try {
            if(file_exists($to)) {
                unlink($to);
            }
            $resultat = rename($from, $to);

            return $resultat;

        } catch( Exception $e ) {
            throw $e;
        }
    }

    public function is_dir($dir) {
        $resultat = is_dir($dir);
        return $resultat;
    }

    public function is_writable($file) {
        $resultat = is_writable($file);
        return $resultat;
    }
    
	public function chmod($file, $droits) {
        $resultat = chmod($file, $droits);
        return $resultat;
    }

    /**
     * Retourne tous les fichiers d'un répertiure commençant par un motif
     * 
     * @param String $dir
     * @param String $endWith
     */

    public function getFilesBeginWithInDir($dir, $beginWith) {
        $fichiers = array();

       try {
           if(!file_exists($dir)) {
               throw new Exception(SinapsApp::getErrorMsg("REPERTOIRE_INEXISTANT", $dir));
           }
           if( is_dir($dir) ) {

               $fichiers = glob($dir . $beginWith . "*");

               if(empty($fichiers)) {
                   throw new Exception(SinapsApp::getErrorMsg("REPERTOIRE_VIDE_MOTIF", $dir, $dir . $beginWith . "*"));
               }

               sort($fichiers);

               $this->absoluToRelatif($dir, $fichiers);
           } else {
               throw new Exception("$dir n'est pas un dossier.");
           }
       } catch( Exception $e ) {
           return JsonService::createErrorResponse($e->getCode(), $e->getMessage());
       }

        return JsonService::createResponse($fichiers, TRUE);
    }

    /**
     * Retourne tous les fichiers d'un répertiure finissant par un motif
     * 
     * @param String $dir
     * @param String $endWith
     */

    public function getFilesEndWithInDir($dir, $endWith) {
        $fichiers = array();

        try {
            if(!file_exists($dir)) {
                throw new Exception(SinapsApp::getErrorMsg("REPERTOIRE_INEXISTANT", $dir));
            }
            if( is_dir($dir) ) {

                $fichiers = glob($dir . "*" . $endWith );

                if(empty($fichiers)) {
                    throw new Exception(SinapsApp::getErrorMsg("REPERTOIRE_VIDE_MOTIF", $dir, $dir . "*" . $endWith));
                }

                sort($fichiers);

                $this->absoluToRelatif($dir, $fichiers);
            } else {
                throw new Exception("$dir n'est pas un dossier.");
            }
        } catch( Exception $e ) {
            return JsonService::createErrorResponse($e->getCode(), $e->getMessage());
        }
        return JsonService::createResponse($fichiers, TRUE);
    }

    /**
     * Transforme un chemin absolu en chemin relatif
     * 
     * @param String $dir  	path jusqu'aux fichiers
     * @param Array $files 	tableau des fichiers à transformer
     */

    public function absoluToRelatif($dir, &$files) {
        $files = array_map(
            function($absolu) use($dir) { 
                return array(
                        "absolu" => $absolu,
                        "relatif" => str_replace($dir, '', $absolu)
                        );

                }
            , $files);
    }

    /**
     * Créer une archive contenant tout les fichiers de $repertoireSource
     * @param String $archive          Chemin complet vers l'archive à créer
     * @param String $repertoireSource Répertoire contenant les fichiers à tarrer
     */
    public function creerTarGz($archive, $repertoireSource, $fichier="*") {
        $cmd = "tar czf $archive $fichier";
        try {
            chdir($repertoireSource);
            $this->systemService->shellExecute($cmd);

            if(!file_exists($archive)) {
				throw new Exception("L'archive $archive n'a pas été créé.");
			}
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Décompresse l'archive vers le dossier spécifié
     * 
     * @param type $cheminArchive Dossier de destination du(es) fichier(s)
     * @param type $destinationDir Fichier archive à décompresser
     * @return boolean
     */
    public function decompresseTarGz($cheminArchive, $destinationDir) {
        // Lancement de l'extraction du fichier temporaire vers le répertoire d'accueil
        $cmd = '/bin/tar -C ' .
                    escapeshellarg($destinationDir) .
                    ' -xzf ' . escapeshellarg($cheminArchive);
        try {
            $this->systemService->shellExecute($cmd);
        } catch(SinapsException $exc){
            $this->setLastMessage("Erreur durant l'exécution de tar: " . $exc->getMessage());
            return FALSE;
        }
    }

    public function scpTo($fileName, $serveur, $user, $path, $userKey=NULL) {
        $cmd = "/usr/bin/scp ";
        if ($userKey)
            $cmd .= "-i $userKey ";

        $cmd .= "$fileName $user@$serveur:$path";

        $this->systemService->shellExecute($cmd);
    }

    public function scpFrom($destination, $fileName, $serveur, $user, $path, $userKey=NULL) {
        $cmd = "/usr/bin/scp ";
        if ($userKey)
            $cmd .= "-i $userKey ";

        $cmd .= "$user@$serveur:$path/$fileName $destination";

        $this->systemService->shellExecute($cmd);
    }

    public function unlink($fileName) {
        if (!is_writable($fileName))
            return FALSE;

        return @unlink($fileName);
    }

    public function getAllOfType($path, $dir, $type) {
        $retour = array();

        if( is_dir($path.$dir) ) {
            $contenu = array_diff(scandir($path.$dir), array('..', '.', '.svn'));
            sort($contenu);

            foreach( $contenu as $dossier) {
                $fichiers = glob($path.$dir . $dossier . "/*." . $type);
                sort($fichiers);
                foreach( $fichiers as $url ) {
                    $retour[$dossier][] = str_replace($path, '', $url);
                }
            }
        }
        return JsonService::createResponse($retour, TRUE);
    }

    public function getExtensionFromPath($path) {
        $ext = explode('.', $path);
        $ext = array_pop($ext);
        return $ext;
    }
    
    /**
     * Renvoie un chemin, (ou un nom de fichier) sans extension
     * @param type $string chaine chemin ou nom de fichier
     * @return type
     */
    public function getCheminSansExtension($string) {
        $elements= explode('.', $string);
        if (count($elements) > 0) {
            array_pop($elements);
        }
        $basename = join('.', $elements);
        return $basename;
    }
}
