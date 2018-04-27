<?php
/**
 * Gere les droits, la modification des droits.
 *
 * PHP version 5
 *
 * @author MSN-Sinaps <esi.lyon-lumiere.msn-socles@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../DTO/GroupeDTO.php";

class GroupeController extends BaseController {

    protected $jqGridService;
    protected $jsonService;
    private $groupeService;

    public function __construct() {
		
        $this->jqGridService = SinapsApp::make("JqGridService");
        $this->jsonService = SinapsApp::make("JsonService");
        $this->groupeService = SinapsApp::make("GroupeService");

        $this->dbh = SinapsApp::make("dbConnection");
        $this->applyFilter("authentification");
        
    }

    // gestion des actions
    public function gestionGroupe() {

        $oper = Input::get('oper');
        $idGroupe = Input::get('id');

        try{

            // operation de création
            if ($oper === 'add') {

                $nom = Input::get('nom');
                $groupeMail = Input::get('groupeMail');
                $groupeTelephone = Input::get('groupeTelephone');
                $groupeDescription = Input::get('groupeDescription');
                $nomSMA = Input::get('nomSMA');

                $nomACreer = urldecode($nom);

                // verifierDoublonNomGroupe lance une exception si le nom existe déjà
                $this->groupeService->verifierDoublonNomGroupe($nomACreer);

                $retourAdd = $this->ajouterGroupe($nomACreer, $groupeMail, $groupeTelephone, $groupeDescription, $nomSMA);
                $retour = $this->jsonService->createResponse($retourAdd);

            // operation de modification
            } elseif ($oper === 'edit') {

                $nom = Input::get('nom');
                $groupeMail = Input::get('groupeMail');
                $groupeTelephone = Input::get('groupeTelephone');
                $groupeDescription = Input::get('groupeDescription');
                $nomSMA = Input::get('nomSMA');

                $nomAModifier = urldecode($nom);
                $groupe = Groupe::find($idGroupe);

                // on verifie que le groupe existe avant de le modifier
                if ($groupe) {

                    $this->groupeService->verifierDoublonNomGroupe($nomAModifier, $idGroupe);
                    // SI (le nom du groupe à modifier est différent de celui passé en parametre ET si celui passé en parametre n'existe pas)
                    // OU (le nom du groupe à modifier est identique à celui passé en parametre)
                    $this->modifierGroupe($idGroupe, $nomAModifier, $groupeMail, $groupeTelephone, $groupeDescription,  $nomSMA);

                } else {
                    throw new Exception("Modification impossible car le groupe n'existe pas.");
                }

                $retour = $this->jsonService->createResponse("");

            // operation de suppression
            } elseif ($oper === 'del') {
                $groupe = Groupe::find($idGroupe);

                if ( $groupe ) {
                    $this->supprimerGroupe($idGroupe);
                } else {
                    throw new Exception("Suppression impossible car le groupe n'existe pas.");
                }
                $retour = $this->jsonService->createResponse($idGroupe);
            }
        } catch(Exception $err) {
            $retour = $this->jsonService->createErrorResponse(500, $err->getMessage());
        }

        return $retour;
    }


    /**
     * Fonction qui ajoute un groupe
     */

    private function ajouterGroupe($nom, $groupeMail, $groupeTelephone, $groupeDescription, $nomSMA) {

        try {
            $groupe = new Groupe();

            $groupe->nom = $nom;
            $groupe->groupeMail = urldecode($groupeMail);
            $groupe->groupeTelephone = urldecode($groupeTelephone);
            $groupe->groupeDescription = urldecode($groupeDescription);
            $groupe->nomSMA = urldecode($nomSMA);
            $groupe->save();

            return $groupe->id;

        } catch(Exception $err) {
            throw $err;
        }
    }

    /**
     * Fonction qui modifie un groupe
     */

    private function modifierGroupe($idGroupe, $nom, $groupeMail, $groupeTelephone, $groupeDescription, $nomSMA) {
        try {

            $groupe = Groupe::find($idGroupe);

            $groupe->nom = $nom;
            $groupe->groupeMail = urldecode($groupeMail);
            $groupe->groupeTelephone = urldecode($groupeTelephone);
            $groupe->groupeDescription = urldecode($groupeDescription);
            $groupe->nomSMA = urldecode($nomSMA);
            $groupe->save();

            // BZ 144902: on modifie aussi dans la table Application les champs moa/moe/exploitant_app/exploitant_sys
            // @@TODO gérer tout par id (5.11)
            $ADG = ApplicationDuGroupe::where('Groupe_id', $groupe->id)
                                      ->whereIsNotNull('exploitant')
                                      ->where('ordre', 1)
                                      ->get();

            foreach($ADG as $adg) {
                $application = Application::find($adg->Application_id);
                if(!$application) {
                     continue;
                }
                $groupe = Groupe::find($adg->Groupe_id);
                switch($adg->exploitant) {
                    case "EA":
                        $application->exploitant_app = $groupe->id;
                        break;
                    
                    case "ES":
                        $application->exploitant_sys = $groupe->id;
                        break;
                    
                    case "MOE":
                        $application->moe = $groupe->id;
                        break;
                    
                    case "MOA":
                        $application->moa = $groupe->id;
                        break;
                    
                    case "ESEA":
                        $application->exploitant_sys = $groupe->id;
                        $application->exploitant_app = $groupe->id;
                        break;
                    
                    case "OEOA":
                        $application->moa = $groupe->id;
                        $application->moe = $groupe->id;
                        break;
                    default:
                        throw new Exception("Type inconnu: ". $adg->exploitant);
                        break;
                    
                }
                $application->save();
                $application->miseAJourClefABesoinDetreReconstruit();
            }

        } catch(Exception $err) {
            throw $err;
        }
    }

    

    /**
     * Fonction de suppression d'un groupe
     * préalable : lister les suppressions à effectuer en amont avant du supprimer les groupes
     * Conséquences de la suppression
     *     1 - suppression du groupe dans les TdB
     *     2 - suppression des habilitations du "groupe à supprimer" dans ApplicationDuGroupe
     *     3 - suppression des utilisateurs appartenant au "groupe à supprimer" dans UtilisateurDuGroupe
     *     4 - suppression le groupe dans Groupe
     */
    private function supprimerGroupe($idGroupe) {

        $groupe = Groupe::find($idGroupe);

        try{

            // On cré une transaction
            OrmQuery::beginTransaction();

            // 1 - suppression du groupe dans les TableauDeBord
            $this->groupeService->supprimerGroupeDuTDB($idGroupe);

            // 2 - suppression des habilitations du "groupe à supprimer" dans ApplicationDuGroupe
            $this->groupeService->supprimerHabilitationsDuGroupe($idGroupe);

            // 3 - suppression des utilisateurs appartenant au "groupe à supprimer" dans UtilisateurDuGroupe
            $this->groupeService->supprimerGroupeDesUsers($idGroupe);

            // 4 - suppression des utilisateurs appartenant au "groupe à supprimer" dans UtilisateurDuGroupe
            $this->groupeService->remiseAZDerogationGroupe($idGroupe);

            // 5 - suppression du groupe final
            $groupe->delete();

            // Validation
            OrmQuery::commit();

        } catch(Exception $err) {
            OrmQuery::rollback();
            throw $err;
        }
    }

    /**
     * Contrôles à effectuer avant suppression du Groupe
     */

    public function controleAvantSuppression() {

        $idGroupe = Input::get('id');
        $message = "";

        $groupeASupprimer = Groupe::find($idGroupe);

        if ( $groupeASupprimer) {
            $retour['nom'] = $groupeASupprimer->nom;
            $retour['tdbs'] = $this->getListeTDBs($idGroupe);
            $retour['acces'] = $this->getListeApplisDuGroupe($idGroupe);
            $retour['utilisateurs'] = $this->getListeDesUsersDuGroupe($idGroupe);
            $retour['derogations'] = $this->getListeDesDerogationsDuGroupe($idGroupe);

            $json = $this->jsonService->createResponse($retour);
            return $json;

        } else {
            $json = $this->jsonService->createErrorResponse(500, "Le groupe n'existe pas.");
            return $json;
        }
    }

    private function getListeTDBs($idGroupe) {

        $listeDesTDBs = $this->groupeService->listeDesTDBsDuGroupe($idGroupe);
        if ( empty($listeDesTDBs) ) {
            $listeDesTDBs[] = "Aucun tableau de bord n'est accessible pour ce groupe.";
        }

        return $listeDesTDBs;
    }

    private function getListeApplisDuGroupe($idGroupe) {

        $listeDesApplisDuGroupe = $this->groupeService->listeDesApplisDuGroupe($idGroupe);
        if ( empty($listeDesApplisDuGroupe) ) {
            $listeDesApplisDuGroupe[] = "Aucune application n'est accessible par ce groupe.";
        }

        return $listeDesApplisDuGroupe;
    }

    private function getListeDesUsersDuGroupe($idGroupe) {

        $listeDesUsersDuGroupe = $this->groupeService->listeDesUsersDuGroupe($idGroupe);
        if ( empty($listeDesUsersDuGroupe) ) {
            $listeDesUsersDuGroupe[] = "Aucun utilisateur ne fait partie dans ce groupe.";
        }

        return $listeDesUsersDuGroupe;
    }

    private function getListeDesDerogationsDuGroupe($idGroupe) {

        $listeDesDerogationsDuGroupe = $this->groupeService->listeDesDerogationsDuGroupe($idGroupe);
        if ( empty($listeDesDerogationsDuGroupe) ) {
            $listeDesDerogationsDuGroupe[] = "Aucun paramètrage des alertes ne fait mention de ce groupe.";
        }

        return $listeDesDerogationsDuGroupe;
    }

    /**
     * Fonction qui retourne la liste des Groupes
     */

    public function getGroupesListe() {

        // On met en oeuvre le tri sur la base des critères métier.
        if (count($_REQUEST) !== 0) {
            $localRequest = $_REQUEST;
        } else {
            // utile pour les tests PHPUnit
            $localRequest = array("sidx" => "nom", "sord" => "asc");
        }
//        $localRequest["sidx"] = str_replace($localRequest["sidx"], $localRequest["sidx"].' '.$localRequest["sord"], $localRequest["sidx"]);

        $sqlQuery = self::SQL_LISTE_GROUPES;

        $dbh = SinapsApp::make("dbConnection");
        $stmt = $dbh->query($sqlQuery);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "GroupeDTO");

        $groupes = $stmt->fetchAll();

        $this->jqGridService->registerCallback( "onFiltrageTermine", function(&$dataList, &$fields) {
            GroupeDTO::onFiltrageTermine($dataList, $fields);
        });

        $liste = $this->jqGridService->createResponseFromModels(
			$groupes,
			$localRequest,
			array(),
			TRUE
		);

        return $liste;
    }

    /**
     * Renvoie la liste des groupes asociées à l'application de tous les niveau (grid habilitations)
     *
     * @return array    Renvoie une liste d'objets "Groupe & profil"
     */

    public function getAllGroupesParNiveau() {

        $niveau=Input::get('niveau', 0);

        $listeRetour = array();
        $listeMemoNomGroupe = array();

        $listeAppGroupes = ApplicationDuGroupe::with('profil')
                                              ->get();

        foreach ($listeAppGroupes as $objAppGroupe) {
            if ($objAppGroupe->profil->niveau == $niveau) {

                $objGroupe = $objAppGroupe->groupe;
                $nomGroupe = $objGroupe->nom;
                if (!in_array($nomGroupe, $listeMemoNomGroupe)) {

                    // @@5.10: on n'utilise plus le champ nomGroupe
                    $obj = new HabilitationDTO();
                    $obj->id            = $objGroupe->id;
                    $obj->mailGroupe    = $objGroupe->groupeMail;
                    $obj->telGroupe     = $objGroupe->groupeTelephone;
                    $obj->idProfil      = $objAppGroupe->profil->id;
                    $obj->niveauProfil  = $objAppGroupe->profil->niveau;
                    $obj->nomProfil     = $objAppGroupe->profil->nom;
                    $listeRetour[]      = $obj;

                    $listeMemoNomGroupe[] = $objGroupe->nom;
                }
            }
        }

        $retour = $this->jqGridService->createResponseFromModels(
                        $listeRetour,
                        $_REQUEST,
                        array(),
                        TRUE
        );

        return $retour;
    }

    public function getGroupesParNiveau() {

        $niveau = Input::get('niveau');

        $listeRetour = array();
        $listeMemoNomGroupe = array();

        $listeAppGroupes = ApplicationDuGroupe::with('profil')->get();
        foreach ($listeAppGroupes as $objAppGroupe) {
            // Si l'objet est du niveau attendu on le retient
            if ($objAppGroupe->profil->niveau == $niveau) {
                $objGroupe = $objAppGroupe->groupe;
                $nomGroupe = $objGroupe->nom;

                if (!in_array($nomGroupe, $listeMemoNomGroupe)) {

                    $listeRetour[] = array(
                                                'id' => $objGroupe->id,
                                                'nom' => $objGroupe->nom
                                        );
                    $listeMemoNomGroupe[] = $objGroupe->nom;
                }
            }
        }

        $liste = JsonService::createResponse($listeRetour);

        return $liste;
    }
    
    public function getDetailUnGroupe() {
		$id = Input::get('id');
		
		$monGroupe = Groupe::find($id)->toArray();
		
		$retour = JsonService::createResponse($monGroupe);
		return $retour;
	}

    const SQL_LISTE_GROUPES = <<<EOF
            SELECT
                "GRP".id, "GRP".nom,
                "GRP"."groupeMail",
                "GRP"."groupeTelephone",
                "GRP"."groupeDescription",
                "GRP"."nomSMA",
                (SELECT count(id)
                    FROM "ApplicationDuGroupe"
                    WHERE "Groupe_id" = "GRP".id) AS "nbApplis",
                (SELECT count(id)
                    FROM "UtilisateurDuGroupe"
                    WHERE "Groupe_id" = "GRP".id) AS "nbUsers"
            FROM "Groupe" AS "GRP"
            ORDER BY "GRP".nom ASC;
EOF;

}
