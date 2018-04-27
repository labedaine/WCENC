<?php
/**
 * Interface devant être implémentée par les sorties de log.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

interface LogWriter {
    public function __construct($nom, array $parametres);
    public function write($niveau, $message);
    public function dump($niveau);
    public function flush();
}