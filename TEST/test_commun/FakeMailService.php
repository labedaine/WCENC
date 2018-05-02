<?php
/**
 * Permet de simuler les paramètres passés par le browser web.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

class FakeMailService {

    static public function init() {
        MailService::init(new FakeMailService());
    }
    
    public function envoyerMail($destinataire, $objet, $message) {
        $this->envoyerMailOnInstance($destinataire, $objet, $message);
    }
    
    public function envoyerMailOnInstance($destinataire, $objet, $message) {
        return true;
    }
    
}
