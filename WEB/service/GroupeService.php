<?php
/**
 * Ensemble de fonctions liées à l'identification.
 *
 * PHP version 5
 *
 * @author MSN-Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 */

class GroupeService {

    protected $jqGridService;

    private $namespace = "";

    /**
     * Constructeur
     */
    public function __construct() {
        $this->jqGridService = SinapsApp::make("JqGridService");
        $this->dbh = SinapsApp::make("dbConnection");
    }


    /**
     * fonction d'identification des TdBs du groupe dans TableauDeBord
     */
    public function listeDesTDBsDuGroupe($groupeId) {
        // inventaire et identification des TdBs du groupe dans TableauDeBord
        $tDBs = TableauDeBord::where('Groupe_id', $groupeId)->get();

        $listeTDBs = array();
        foreach ($tDBs as $obj) {
            $listeTDBs[] =  $obj->nom;
        }
        return $listeTDBs;
    }

    /**
     * fonction d'identification des applications du groupe dans ApplicationDuGroupe
     */
    public function listeDesApplisDuGroupe($groupeId) {
        // inventaire des applications du groupe dans ApplicationDuGroupe
        $applisDuGroupe = ApplicationDuGroupe::where('Groupe_id', $groupeId)->get();

        // identification des users du groupe dans UtilisateurDuGroupe
        $listeApplis = array();
        foreach ($applisDuGroupe as $appliDuGroupe) {
            $listeApplis[] = Application::where('id', $appliDuGroupe->Application_id)->first()->nom;
        }
        return $listeApplis;
    }


    /**
     * fonction d'identification des utilisateurs du groupe dans UtilisateurDuGroupe
     */
    public function listeDesUsersDuGroupe($groupeId) {
        // inventaire des users du groupe dans UtilisateurDuGroupe
        $users = UtilisateurDuGroupe::where('Groupe_id', $groupeId)->get();

        // identification des users du groupe dans UtilisateurDuGroupe
        $listeUsers = array();
        foreach ($users as $user) {
            $resultat = Utilisateur::where('id', $user->Utilisateur_id)->first();
            $listeUsers[] = trim($resultat->prenom ." ". $resultat->nom);
        }
        return $listeUsers;
    }

    /**
     * fonction d'identification des paramétrages des alertes du groupe dans Derogation
     */
    public function listeDesDerogationsDuGroupe($groupeId) {
        // inventaire des derogations du groupe
        $groupe = Groupe::find($groupeId);
        if($groupe) {
            $derogations = Derogation::where('destinataireAlerte', $groupeId)->get();
        }

        $listeDerogations = array();
        foreach ($derogations as $derogation) {
            $dateModif = date('d/m/Y H:i:s', $derogation->dateModification);
            $listeDerogations[] = trim($derogation->nomCompletIndicateurEtat) . " (Ajouté le ". $dateModif .")";
        }
        return $listeDerogations;
    }

    /**
     * fonction de suppression du groupe dans TableauDeBord
     */

    public function supprimerGroupeDuTDB($id) {
        $objArr = TableauDeBord::where('Groupe_id', $id)->get();
        if(!empty($objArr)) {
            foreach($objArr as $obj) {
                $obj->delete();
            }
        }
    }

    /**
     * fonction de suppression des habilitations du groupe dans ApplicationDuGroupe
     */

    public function supprimerHabilitationsDuGroupe($id) {
        $objArr = ApplicationDuGroupe::where('Groupe_id', $id)->get();
        if(!empty($objArr)) {
            foreach($objArr as $obj) {
                $obj->delete();
            }
        }
    }

    /**
     * fonction de suppression du groupe des utilisateurs dans UtilisateurDuGroupe
     */

    public function supprimerGroupeDesUsers($id) {
        $objArr = UtilisateurDuGroupe::where('Groupe_id', $id)->get();
        
        if(!empty($objArr)) {
            foreach($objArr as $obj) {
                $obj->delete();
            }
        }
    }

     /**
     * fonction qui met à null le champ destinataireAlerte dans le cas d'une dérogation
     */

    public function remiseAZDerogationGroupe($groupeId) {
        // @@TODO mettre l'id du groupe dans le champs destinataireAlerte
        $objArr = Derogation::where('destinataireAlerte', $groupeId)->get();
        if(!empty($objArr)) {
            foreach($objArr as $obj) {
                $obj->destinataireAlerte = NULL;
                $obj->save();
            }
        }
    }

    /**
     * fonction permettant de rechercher si le nom du groupe existe déjà en base
     *
     * @param String $nomGroupe nom a rechercher
     * @param String $exclure à exclure de la recherche, cas du modifier
     */

    public function verifierDoublonNomGroupe($nomGroupe, $exclure=NULL) {
        $groupe = Groupe::whereUpper('nom', strtoupper($nomGroupe));

        if($exclure) {
            $groupe->where('id', '!=', $exclure);
        }
        $groupe = $groupe->first();

        if ( $groupe ) {
            throw new Exception("Action impossible car le groupe $nomGroupe existe déjà (".$groupe->nom.").");
        }
    }
}
