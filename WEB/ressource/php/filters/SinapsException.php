<?php
/**
 * Classe de base des exceptions de l'application sinaps.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class SinapsException extends Exception {
    public function __construct($message, $code=0, Exception $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }
}