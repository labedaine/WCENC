<?php
/**
 * Classe SystemService.
 *
 * PHP version 5
 *
 * @author Philippe Jung <philippe-1.jung@dgfip.finances.gouv.fr>
 */

 class SystemService {

    public function isProcessRunning($cmd) {
        $cmd = "/usr/bin/pgrep -f " . escapeshellarg($cmd);
        exec($cmd, $output, $exitCode);
        if ($exitCode == 0)
            return TRUE;
        else if ($exitCode == 1)
            return FALSE;
        else if ($exitCode == 2)
            throw new SinapsException("pgrep: Erreur de syntaxe sur la ligne de commande");
        else 
            throw new SinapsException("pgrep: Erreur fatale : plus assez de mémoire, etc.");
    }

    /**
     * Exécute une commande système
     *
     * Exécute une commande système et revoie stdout si l'exit code est 0
     * Sinon lève une exception contenant l'exit code dans le code d'erreur
     * et le stdout dans message. Chaque ligne de stdout est fusionnée en une
     * ligne unique à travers à appel à implode dont le caractère de jointure
     * est passé à shellExecute (défaut: espace)
     */
    public function shellExecute($cmd, $implodeStr=' ') {
        ob_start();
        exec($cmd ." 2>&1", $arrRes, $exitCode);
        ob_end_clean();

        if ($exitCode !== 0) {
            throw new SinapsException(implode($implodeStr, $arrRes), $exitCode);
        }
        return $arrRes;
    }

    public function shellSystem($cmd) {
        system($cmd);
    }

    public function ssh($cmd, $serveur, $user, $userKey=NULL, array $options=array()) {
//        print "$cmd\n";
        $shellCmd = "/usr/bin/ssh ";
        if ($userKey)
            $shellCmd .= "-i $userKey ";

        foreach($options as $optionName => $optionValue) {
            $shellCmd .= "-o $optionName=$optionValue ";
        }

        $shellCmd .= "$user@$serveur -- \"$cmd \"";
        $this->shellExecute($shellCmd);
    }
    
    /**
     * Renvoie TRUE si un PID existe
     * @param type $pid
     * @return boolean
     */
    public function pidExiste($pid) {
            $infosPID = $this->getpidinfo($pid);
            // et on vérifie que le processus existe toujours
            if ($infosPID ) {
                if ($infosPID['PID'] === (string) "$pid") {
                    return TRUE;
                }
            }
            return FALSE;
    }
    
    /**
     * Renvoie les infos sur un processus donné
     * Si le processus n'existe pas, renvoie faux
     * @param type $pid
     * @param type $ps_opt
     * @return boolean|string
     */
    public function getpidinfo($pid, $ps_opt="u"){

       $psCmd=shell_exec("ps ".$ps_opt."p ".$pid);
       $ps=explode("\n", $psCmd);

       if(count($ps)<2){
//          trigger_error("PID ".$pid." doesn't exists", E_USER_WARNING);
          return FALSE;
       }

       foreach($ps as $key=>$val){
          $ps[$key]=explode(" ", ereg_replace(" +", " ", trim($ps[$key])));
       }

       foreach($ps[0] as $key=>$val){
          // BZ 138942: On ajoute un contrôle
          if(!isset($ps[1][$key])) {
              return FALSE;
          }
          $pidinfo[$val] = $ps[1][$key];
          unset($ps[1][$key]);
       }

       if(is_array($ps[1])){
          $pidinfo[$val].=" ".implode(" ", $ps[1]);
       }
       return $pidinfo;
    } 

}
