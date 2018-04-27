<?php
/**
 * Controleur ApplicationRestController.
 *
 * PHP version 5
 *
 * @author Philippe Jung <philippe-1.jung@dgfip.finances.gouv.fr>
 */



class ApplicationRestController extends BaseController {

    protected $jqGridService;
    protected $jsonService;

    private $monFiltreVide;

    public function __construct() {
        $this->jqGridService = SinapsApp::make("JqGridService");
        $this->jsonService = SinapsApp::make("JsonService");
        $this->dbh = SinapsApp::make("dbConnection");
        SinapsApp::bind(
            "GestionApplicationService",
            function () {
                return new GestionApplicationService();
            }
        );
        $this->GestionApplicationService = SinapsApp::make("GestionApplicationService");
    }

    /**
     * Fonction qui retourne la liste des Applications
     */

    public function getListeApplications() {

        // Gestion du filtre vide
        $this->monFiltreVide = Input::get('monFiltreVide', FALSE);

        $applications = Application::all();

        $listeApplications = array();

        foreach ($applications as $appli) {

            $applicationDTO = new ApplicationDTO();

            $applicationDTO->id = $appli->id;
            $applicationDTO->application = $appli->nom;
            $applicationDTO->nomSMA = $appli->nomSMA;
            $applicationDTO->creationSMA = $appli->creationSMA;
            $applicationDTO->resolutionSMA = $appli->resolutionSMA;

            $tampon = array();

            foreach($appli->groupes as $appDuGroupe) {
                $tampon[$appDuGroupe->profil->nom][] = $appDuGroupe->groupe->nom;
                sort($tampon[$appDuGroupe->profil->nom]);
            }
            foreach($tampon as $niveau => $valeur) {
                $applicationDTO->$niveau = join('<br />', $valeur);
            }

            if($this->monFiltreVide === 'true') {
                if( !is_null($applicationDTO->N0) &&
                    !is_null($applicationDTO->N1) &&
                    !is_null($applicationDTO->N2) &&
                    !is_null($applicationDTO->N3) ) {
                        continue;
                    }
            }

            $listeApplications[] = $applicationDTO;
        }

        // On met en oeuvre le tri sur la base des critères métiers.
        if (count($_REQUEST) !== 0) {
            $localRequest = $_REQUEST;
        } else {
            // utile pour les tests PHPUnit
            $localRequest = array("sidx" => "application", "sord" => "asc");
        }
        $localRequest["sidx"] = str_replace($localRequest["sidx"], $localRequest["sidx"].' '.$localRequest["sord"], $localRequest["sidx"]);

        $liste = $this->jqGridService->createResponseFromModels(
                        $listeApplications,
                        $localRequest,
                        array(),
                        TRUE
        );

        return $liste;
    }

    /**
     * Renvoie la liste des groupes de l'application pour un niveau donné
     *
     * @param type $idAopplication Id de l'application
     * @param type $niveau
     * @return type
     */

    public function getGroupesPourNiveau() {
        $idApplication = Input::get('idApplication');
        $niveau = Input::get('niveau');
        $objApp = Application::where('id', $idApplication)->first();

        $result["groupes"] = $objApp->getListeGroupesAvecNiveau($niveau);

        $retour = $this->jsonService->createResponseFromArray($result);
        return $retour;
    }

    /**
     * Renvoie la liste des groupes de l'application pour tous les niveau
     *
     * @param type $idAopplication Id de l'application
     * @param type $niveau
     * @return type
     */

    public function getGroupesEtNiveau() {
        $idApplication = Input::get('idApplication');
        $groupesProfil = array();

        $objApp = Application::where('id', $idApplication)->first();

        if($objApp) {
            $groupesProfil = $objApp->getListeGroupesEtNiveau();
        }

    $retour = $this->jqGridService->createResponseFromModels(
                        $groupesProfil,
                        $_REQUEST,
                        array(),
                        TRUE
        );

        return $retour;
    }

    /**
     * Renvoie la liste des Macro-Domaines
     * Renvoie le détail d'une ou plusieurs applications
     * @param array $listeIdsApplications un tableau d'identifiants d'applications
     * @return mixed renvoie une chaîne json
     */

    public function getDetails() {

        $response = array();
        $tableCorrespondaineMacroDomaine_Domaine = $this->GestionApplicationService->getMacroDomaineDomaine();

        //il s'agit d'une nouvelle application
        $listeIdsApplications = Input::get('listeIdsApplications');

        $response[] = $tableCorrespondaineMacroDomaine_Domaine;
        $retour = $this->jsonService->createResponse($response);

        if (count($listeIdsApplications) > 0) {

            $listeApps = Application::whereIn('id', $listeIdsApplications)->get();
            foreach ($listeApps as $application) {
                $responsedetails[] = array("id" => $application->id,
                                    "nom" => $application->nom,
                                    "nomSMA" => $application->nomSMA,
                                    "creationSMA" => $application->creationSMA,
                                    "resolutionSMA" => $application->resolutionSMA,
                                    "description" => ($application->description?$application->description:""),
                                    "equipe_prox" => ($application->equipe_prox?$application->equipe_prox:""),
                                    "exploitant_app" => ($application->exploitant_app?html_entity_decode($application->exploitant_app):""),
                                    "exploitant_sys" => ($application->exploitant_sys?html_entity_decode($application->exploitant_sys):""),
                                    "moa" => ($application->moa?html_entity_decode($application->moa):""),
                                    "moe" => ($application->moe?html_entity_decode($application->moe):""),
                                    "HNO" => $application->HNO,
                                    "dateDernierDeploiement" => $application->dateDernierDeploiement,
                                    "Domaine_id" => $application->Domaine_id,
                                    "Moteur_id" => $application->Moteur_id,
                                    "MacroDomaine" => $application->domaine->macrodomaine->nom,
                                    "Domaine" => $application->domaine->nom
                                 );
                array_push($response,$responsedetails);
            }
            $retour = $this->jsonService->createResponseFromArray($response);
        }
        return $retour;
    }

    /**
     * Permet de faire une recherche d'existance sur les champs nom ou id.
     * Fonctionne aussi sur les autres champs car pas de contrôle
     * @param string $nomColonne nom de la colonne pour la recherche
     * @param string $valeurChamp valeur à rechercher
     * @return bool retourne true si l'application est trouvée en base false sinon
     */

    public function isApplicationExiste() {
        $nomColonne = Input::get('nomColonne');
        $valeurChamp = Input::get('valeurChamp');

        try {
            $listeApplication = Application::where($nomColonne,$valeurChamp);
        } catch (Exception $e) {
            throw $e;
        }

        if ( $listeApplication->count() != 0 ) {
            $retour = $this->jsonService->createResponse("true");
            return $retour;
        }
        $retour = $this->jsonService->createResponse("false");
        return $retour;
    }

    /* Modification de l'application en base avec les données provenant de la gestion des applications
     * @param int $domaine Domaine de l'application
     * @param int $idApplication Id de l'application à modifier
     * @param string $description Description de l'application
     * @return mixed renvoi l'Id de l'application modifié ou 0 sinon
     */

    public function modifieApplication() {

        $domaine = Input::get('domaine');
        $idApplication = Input::get('idApplication');
        $description = Input::get('description');
        $nomSMA = Input::get('nomSMA');
        $creationSMA = Input::get('creationSMA', 0);
        $resolutionSMA = Input::get('resolutionSMA', 0);

        try {
            //recupération de l'application en base
            $monApplication = Application::find($idApplication);

            if(!$monApplication) {
                throw new Exception("L'application d'id $idApplication n'existe pas.");
            }

            $monApplication->Domaine_id = $domaine;
            $monApplication->description = $description;
            if($nomSMA === "") {
                $nomSMA = NULL;
            }
            $monApplication->nomSMA = $nomSMA;
            $monApplication->creationSMA = $creationSMA;
            $monApplication->resolutionSMA = $resolutionSMA;
            $monApplication->save();

            $ret = $monApplication->id;

        } catch ( Exception $e ){
            $retour = $this->jsonService->createErrorResponse(500, $e->getMessage());
            return $retour;
        }

        $retour = $this->jsonService->createResponse($ret);
        return $retour;
    }

    /**
     * Fonction qui enregistre en base la nouvelle application.
     *
     * @param int $domaine              Domaine de la nouvelle application
     * @param string $nomApplication    Nom de la nouvelle application
     * @param string $nomSMA            Nom SMA de la nouvelle application
     * @param string $description       description de la nouvelle application
     * @param string $creationSMA       période et activé ou non pour la création SMA
     * @param string $resolutionSMA       période et activé ou non pour la resolution SMA
     * @return mixed retourne l'id de la nouvelle application ou 0 sinon
     */

    public function saveNewApplication() {

        $domaine = Input::get('domaine');
        $nomApplication = Input::get('nomApplication');
        $nomSMA = Input::get('nomSMA');
        $description = Input::get('description');
        $creationSMA = Input::get('creationSMA', 0);
        $resolutionSMA = Input::get('resolutionSMA', 0);

        // Si l'application n'existe pas en Base, on peut la créer.
        try {
            $monApplication = Application::where("nom", $nomApplication)->first();
            if(!$monApplication) {

                //mise à jour de la table Application
                $newApplication = new Application;
                $newApplication->nom = $nomApplication;
                if($nomSMA === "") {
                    $nomSMA = NULL;
                }
                $newApplication->nomSMA = $nomSMA;
                $newApplication->creationSMA = $creationSMA;
                $newApplication->resolutionSMA = $resolutionSMA;
                $newApplication->description = $description;
                $newApplication->Domaine_id = $domaine;
                $newApplication->save();

                $ret = $newApplication->id;

            } else {
                throw new Exception("L'application $nomApplication existe déjà.");
            }
        } catch ( Exception $e ) {
            $retour = $this->jsonService->createErrorResponse(500, $e->getMessage());
            return $retour;
        }

        $retour = $this->jsonService->createResponse($ret);
        return $retour;
    }

    /**
     * Fonction qui retourne l'état de la gestion globale SMA
     *
     * @return mixed retourne un objet sur l'état de la gestion SMA
     */

    public function getEtatGestionSMA() {

        try {
            $eventCreation   = Evenement::where("nom", Evenement::DESACTIVATION_CREATION_SMA)->first();
            $eventResolution = Evenement::where("nom", Evenement::DESACTIVATION_RESOLUTION_SMA)->first();

            // Création des variables de retour et de défaut
            $response = array();

            $creation = array();
            $creation["actif"] = 1;
            $creation["depuis"] = NULL;

            $resolution = array();
            $resolution["actif"] = 1;
            $resolution["depuis"] = NULL;

            // Création
            if($eventCreation) {
                $creation["actif"] = 0;
                $creation["depuis"] = $eventCreation->dateDebut;
            }
            // Résolution
            if($eventResolution) {
                $resolution["actif"] = 0;
                $resolution["depuis"] = $eventResolution->dateDebut;
            }
            $response["creation"]   = $creation;
            $response["resolution"] = $resolution;

        } catch(Exception $e) {
            $retour = $this->jsonService->createErrorResponse(500, $e->getMessage());
            return $retour;
        }
        $retour = $this->jsonService->createResponse($response);
        return $retour;
    }

    /**
     * Fonction qui met à jour l'état de la gestion globale SMA
     *
     * @return mixed retourne un objet sur l'état de la gestion SMA
     */

    public function metAJourGestionSMA() {

        $creation   = Input::get('creation');
        $resolution = Input::get('resolution');

        try {
            $eventCreation   = Evenement::where("nom", Evenement::DESACTIVATION_CREATION_SMA)->first();
            $eventResolution = Evenement::where("nom", Evenement::DESACTIVATION_RESOLUTION_SMA)->first();

            // Gestion de la création
            // Si on demande l'activation de la création
            if(!$creation) {
                // Si l'évenement n'existe pas, on le cré et on le démarre
                if(!$eventCreation) {
                    $eventC = Evenement::getOrCreate(Evenement::DESACTIVATION_CREATION_SMA, "");
                    $eventC->demarrer();
                }
            } else {
                // On arrête la désactivation
                if($eventCreation) {
                    $eventCreation->delete();
                }
            }

            // Gestion de la création
            // Si on demande l'activation de la résolution
            if(!$resolution) {
                // Si l'évenement n'existe pas, on le cré et on le démarre
                if(!$eventResolution) {
                    $eventR = Evenement::getOrCreate(Evenement::DESACTIVATION_RESOLUTION_SMA, "");
                    $eventR->demarrer();
                }
            } else {
                // On arrête la désactivation
                if($eventResolution) {
                    $eventResolution->delete();
                }
            }
        } catch(Exception $e) {
            $retour = $this->jsonService->createErrorResponse(500, $e->getMessage());
            return $retour;
        }

        $retour = $this->jsonService->createResponse("");
        return $retour;
    }

}
