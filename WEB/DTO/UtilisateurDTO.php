<?php

/**
 * Classe chargée de gérer les objets pour la vue des Utilisateurs.
 *
 * PHP version 5
 *
 * @author MSN-Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 * 
 * PATCH_5_09 : Classe Utilisateur propre à IHMR
 */

use models\configuration\Groupe;
use models\configuration\Utilisateur;

class UtilisateurDTO extends SinapsModel {
    protected $nom;
    protected $prenom;
    protected $login;
    protected $email;
    protected $groupes;
    protected $role;
    
    static function onFiltrageTermine( &$rawData, &$fields) {
        $nbDatas = count($rawData);
        if ($nbDatas === 0) {
            return;
        }
        
    }
}
