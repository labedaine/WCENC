<?php
/**
 * Permet l'envoi de mail
 *
 * PHP version 5
 * 
 * @author Damien André <damien.andre@dgfip.finances.gouv.fr>
 */

class MailService {
    
    const OBJECT_MAIL_ACTIVATION_UTILISATEUR = "Activation Compte BetFip";
    const MESSAGE_MAIL_ACTIVATION_UTILISATEUR = "Bienvenue @NOM@,\n\nNous sommes ravis de vous accueillir parmi nos nouveaux utilisateurs.\n\nÀ bientôt,\nL'équipe BetFip";
   
    const OBJECT_MAIL_MDP = "Nouveau mot de passe BetFip";
    const MESSAGE_MAIL_MDP = "Bienvenue @NOM@,\n\nVotre nouveau mot de passe est @MDP@.\n\nÀ bientôt,\nL'équipe BetFip";
     
    
    static protected $instance = NULL;
    
    static public function init($implementation=NULL) {
        if ( $implementation !== NULL) {
            static::$instance = $implementation;
        } else if ( static::$instance === NULL) {
            self::$instance = new MailService();
        }
    }
    
    /**
     * Envoi un mail 
     * 
     * @param String $destinataire
     * @param String $objet
     * @param String $message
     * 
     * @return String la structure sérialisée au format Json
     */
    public function envoyerMail($destinataire, $objet, $message) {
        static::init();
        $retour = static::$instance->envoyerMailOnInstance($destinataire, $objet, $message);
        return $retour;
    }
    
    /**
     * Envoi un mail d'activation
     *
     * @param String $destinataire
     * @param String $prenom
     * @param String $objet
     * @param String $message
     *
     * @return String la structure sérialisée au format Json
     */
    public function envoyerMailActivationCompte($destinataire, $prenom) {
        static::init();
        $retour = static::$instance->envoyerMailOnInstance($destinataire, self::OBJECT_MAIL_ACTIVATION_UTILISATEUR, str_replace("@NOM@", $prenom, self::MESSAGE_MAIL_ACTIVATION_UTILISATEUR));
        return $retour;
    }
    
    public function envoyerMailMdp($destinataire, $prenom, $mdp) {
        static::init();
        $texte = str_replace("@NOM@", $prenom, self::MESSAGE_MAIL_MDP);
        $texte = str_replace("@MDP@", $prenom, $texte);
        $retour = static::$instance->envoyerMailOnInstance($destinataire, self::OBJECT_MAIL_MDP, $texte);
        return $retour;
    }
    
    public function envoyerMailOnInstance($destinataire, $objet, $message) {
        
        $test = SinapsApp::getConfigValue("email.notification.test");
        if( $test !== NULL ) {
            $destinataire = $test;
        }

        try {
            
            $from = SinapsApp::getConfigValue("email.notification.expediteur");
            $replyTo = SinapsApp::getConfigValue("email.notification.replyto");
            // Construction de l'en-tête du mail
            $headers   = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/plain; charset=UTF-8";
            $headers[] = "From: ".$from;
            $headers[] = "Reply-To: ".$replyTo;
            $headers[] = "Subject: {$objet}";
            $headers[] = "X-Mailer: PHP/".phpversion();
            
            $retour = mail($destinataire, $objet, $message,  implode ("\r\n", $headers));
            return $retour;
            
        } catch(Exception $e) {
            throw $e;
        }
        
    }
}
