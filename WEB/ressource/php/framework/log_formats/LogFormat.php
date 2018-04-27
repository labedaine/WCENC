<?php
/**
 * Interface devant être respectée par les formaters de log.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

interface LogFormat {
    public function __construct($nom=NULL, array $parametres=array());
    public function format($niveau, $message);
    public function debuterEtape($nomEtape, $message="");
    public function finirEtape($message, $nomEtape);
}