<?php
/**
 * Classe AlerteController.php.
 *
 * PHP Version 5
 *
 * @author dgfip <dgfip@dgfip.finances.gouv.fr>
 */

require_once __DIR__."/../DTO/AlerteDTO.php";


class AlerteController extends BaseController {
    protected $jqGridService;
    protected $jsonService;
    protected $timeService;
    protected $dateService;
    protected $memcacheService;
    protected $collecteurDemandeService;

    private $dbh;
    private $listeApplications;
    private $monFiltreAEC;
    private $jqgridFilter = NULL;

    /** Motif du filtre de recherche
    {"groupOp":"AND","rules":[
            {"field":"nomComplet","op":"cn","data":"composant"},
            {"field":"date","op":"le","data":"1111"},
            {"field":"etat","op":"bw","data":"1"},
            {"field":"nom","op":"cn","data":"opérateur"},
            {"field":"commentaire","op":"bw","data":"commentaire"}
         ]}
    */

    // Tableau de correspondance entre champ jqgrid et champ sql
    protected $tableauCorrespondance = array(
        'nomComplet'        => ' alerte."nomCompletIndicateurEtatDeclencheur" ',
        'date'              => " extract(epoch from alerte.\"dateCreation\"::TIMESTAMP WITH TIME ZONE) ",
        'dateBascule'       => " extract(epoch from alerte.\"dateDateBascule\"::TIMESTAMP WITH TIME ZONE) ",
        'nom'               => ' alerte."loginUtilisateurEnCharge" ',
        'statutAlerte'      => ' alerte."statutAlerte" ',
        'commentaire'       => ' CONCAT(commentaire.commentaire ,
                                        commentaire."messageAlerte",
                                        commentaire."donneesConstitutives",
                                        commentaire.consigne,
                                        commentaire."destinataireAlerte") ',
        'etat'              => ' alerte.etat ',
        'ticket'            => ' alerte.ticket ',
        'astreinte'         => " CASE WHEN (derog.\"genAstreinte\" IS NULL OR derog.\"genAstreinte\"=0 THEN 'Non' ELSE CONCAT('Oui: ', derog.\"astreinteNumTel\") END ",
        'creationFicheObligatoire'   => ' ie."creationFicheObligatoire" ',
        'dateCn'            => " to_char( alerte.\"dateCreation\", 'dd/mm/YY HH24:MI:SS') ",
        'dateBasculeCn'     => " to_char( alerte.\"dateBasculeNouvelle\", 'dd/mm/YY HH24:MI:SS') ",
        'dateFinSuspension' => ' alerte."dateFinSuspension" ',
        'typeAlerte'        => ' alerte."typeAlerte" '
    );

    public function __construct() {
        $this->beforeFilter('authentification');

        $this->jqGridService    = SinapsApp::make("JqGridService");
        $this->timeService      = SinapsApp::make("TimeService");
        $this->jsonService      = SinapsApp::make("JsonService");
        $this->dateService      = SinapsApp::make("DateService");
        $this->memcacheService  = SinapsApp::make("MemcacheService");
        $this->collecteurDemandeService = SinapsApp::make("CollecteurDemandeService");
        $this->dbh = SinapsApp::make("dbConnection");
    }

    /**
     * Fonction appelée par défaut sur /alerte
     */

    public function getIndex() {
        $retour = $this->getListeNouvelles();
        return $retour;
    }

    /**
     * Renvoie la liste des alertes nouvelles ou prises en compte.
     *
     * Les alertes doivent $etre nouvelles (statut 0) ou prise en compte par moi
     * ou par quelqu'un d'autre (statut 2)
     */
    private function renvoieAlertesNouvellesOuPrisesEnCompte() {
		
		/**
		 * Le filtre doit cacher les alertes qui doivent être traitée par SMA et qui ne l'ont pas encore été
		 * 
		 *  - la liaison SMA de création doit être actif en général => pas d'évenement DESACTIVATION_CREATION_SMA,
		 *  - la création doit être activée pour l'application dans la période dans laquelle on est,
		 *  - il y a une ligne d'échec (status=0) dans FicheIncidentCreation
		 */

		// On récupère l'évenement
		$event = Evenement::where('nom', Evenement::DESACTIVATION_CREATION_SMA)->first();

		// Un évenement donc la création automatique est désactivée de manière globale
		if($event) {
			$filtreAlertesSMA = " 1=1 ";

		} else {
			// Pour savoir à quel moment de la journée on est
			$isHO = $this->dateService->isHO();

			// Si on est en HO alors on filtre que les alertes créé en HO
			if($isHO) {

				// On prends toutes les applications non gérées par SMA en HO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (101, 111) OR (app.\"creationSMA\" IN (101, 111) AND (fic.statut NOT IN (0, 1, 2)))) ";
			} else {

				// On prends toutes les applications non gérées par SMA en HNO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (110, 111) OR (app.\"creationSMA\" IN (110, 111) AND (fic.statut NOT IN (0, 1, 2)))) ";
			}
		}

        $stmt = $this->renvoieStatementSQL(
            array(
				self::SQL_WHERE_ALERTES_NOUVELLES_OU_PRISES_EN_COMPTE,
				$filtreAlertesSMA
            )
        );
        $stmt->execute();
        $alertes = $stmt->fetchAll();
        return $alertes;
    }

    private function renvoieAlertesQueJaiPrisesEnCompte() {
		
		/**
		 * Le filtre doit cacher les alertes qui doivent être traitée par SMA et qui ne l'ont pas encore été
		 * 
		 *  - la liaison SMA de création doit être actif en général => pas d'évenement DESACTIVATION_CREATION_SMA,
		 *  - la création doit être activée pour l'application dans la période dans laquelle on est,
		 *  - il y a une ligne d'échec (status=0) dans FicheIncidentCreation
		 */

		// On récupère l'évenement
		$event = Evenement::where('nom', Evenement::DESACTIVATION_CREATION_SMA)->first();

		// Un évenement donc la création automatique est désactivée de manière globale
		if($event) {
			$filtreAlertesSMA = " 1=1 ";

		} else {
			// Pour savoir à quel moment de la journée on est
			$isHO = $this->dateService->isHO();

			// Si on est en HO alors on filtre que les alertes créé en HO
			if($isHO) {

				// On prends toutes les applications non gérées par SMA en HO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (101, 111) OR (app.\"creationSMA\" IN (101, 111) AND (fic.statut NOT IN (0, 1, 2)))) ";
			} else {

				// On prends toutes les applications non gérées par SMA en HNO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (110, 111) OR (app.\"creationSMA\" IN (110, 111) AND (fic.statut NOT IN (0, 1, 2)))) ";
			}
		}
		
        $stmt = $this->renvoieStatementSQL(
            array(
                self::SQL_WHERE_ALERTES_DE_UTILISATEUR,
                self::SQL_WHERE_ALERTES_PRISES_EN_COMPTE,
                $filtreAlertesSMA
            )
        );
        $stmt->execute();

        $alertes = $stmt->fetchAll();
        return $alertes;
    }

    private function renvoieAlertesEnCoursDeTraitement() {

		/**
		 * Le filtre doit cacher les alertes qui doivent être traitée par SMA et qui ne l'ont pas encore été
		 * 
		 *  - la liaison SMA de création doit être actif en général => pas d'évenement DESACTIVATION_CREATION_SMA,
		 *  - la création doit être activée pour l'application dans la période dans laquelle on est,
		 *  - il y a une ligne d'échec (status=0) dans FicheIncidentCreation
		 */

		// On récupère l'évenement
		$event = Evenement::where('nom', Evenement::DESACTIVATION_CREATION_SMA)->first();

		// Un évenement donc la création automatique est désactivée de manière globale
		if($event) {
			$filtreAlertesSMA = " 1=1 ";

		} else {
			// Pour savoir à quel moment de la journée on est
			$isHO = $this->dateService->isHO();

			// Si on est en HO alors on filtre que les alertes créé en HO
			if($isHO) {

				// On prends toutes les applications non gérées par SMA en HO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées en création
				// + les applis gérées par SMA dont les alertes
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (101, 111) OR (app.\"creationSMA\" IN (101, 111) AND (COALESCE(fic.statut,0) NOT IN (0, 1)))) ";
			} else {

				// On prends toutes les applications non gérées par SMA en HNO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées en création
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (110, 111) OR (app.\"creationSMA\" IN (110, 111) AND (COALESCE(fic.statut,0) NOT IN (0, 1)))) ";
			}
		}

        if ($this->monFiltreAEC === 'true') {
            $stmt = $this->renvoieStatementSQL(array(self::SQL_WHERE_AEC_FILTRE, $filtreAlertesSMA));
        } else {
            $stmt = $this->renvoieStatementSQL(array(self::SQL_WHERE_ALERTES_EN_COURS, $filtreAlertesSMA));
        }
        $stmt->execute();

        $alertes = $stmt->fetchAll();

        return $alertes;
    }

    private function renvoieToutesLesAlertes() {
        $stmt = $this->renvoieStatementSQL(array());
        $stmt->execute();

        $alertes = $stmt->fetchAll();
        return $alertes;
    }

    private function parseParametres() {
        $this->listeApplications = Input::get('mesApplications');
        $this->monFiltreAEC = Input::get('monFiltreAEC', FALSE);

        // Gestion du cas particulier mesApplications=[0]
        foreach ($this->listeApplications as $index => $appId) {
            if ($appId == 0) {
                unset($this->listeApplications[$index]);
                break;
            }
        }
        if( Input::get('_search') !== 'false' )
           $this->jqgridFilter = json_decode(Input::get('filters'));
    }

    /**
     * Fournit la liste des nouvelles alertes au format JqGrid
     *
     * Ceci est un service web exposé. Il renvoie la liste des nouvelles alertes dont l'utilisateur
     * a la charge plus la liste des alertes prises en compte par l'utilisateur.
     */
    public function getListeNouvelles() {

        $this->parseParametres();
		/**
		 * Le filtre doit cacher les alertes qui doivent être traitée par SMA et qui ne l'ont pas encore été
		 * 
		 *  - la liaison SMA de création doit être actif en général => pas d'évenement DESACTIVATION_CREATION_SMA,
		 *  - la création doit être activée pour l'application dans la période dans laquelle on est,
		 *  - il y a une ligne d'échec (status=0) dans FicheIncidentCreation
		 */

		// On récupère l'évenement
		$event = Evenement::where('nom', Evenement::DESACTIVATION_CREATION_SMA)->first();
		// Un évenement donc la création automatique est désactivée de manière globale
		if($event) {
			$filtreAlertesSMA = " 1=1 ";
		} else {
			// Pour savoir à quel moment de la journée on est
			$isHO = $this->dateService->isHO();
			// Si on est en HO alors on filtre que les alertes créé en HO
			if($isHO) {

				// On prends toutes les applications non gérées par SMA en HO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées en création
				// + les applis gérées par SMA dont les alertes
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (101, 111) OR (app.\"creationSMA\" IN (101, 111) AND (COALESCE(fic.statut,2) NOT IN (0, 1)))) ";
			} else {

				// On prends toutes les applications non gérées par SMA en HNO
				// + les applis gérées par SMA dont les alertes ont déjà été traitées en création
				$filtreAlertesSMA = " ( app.\"creationSMA\" NOT IN (110, 111) OR (app.\"creationSMA\" IN (110, 111) AND (COALESCE(fic.statut,2) NOT IN (0, 1)))) ";
			}
		}
        // On commence par aller chercher les alertes nouvelles ou prises en compte, sans ticket
        // rattachées à une application dont l'utilisateur a la charge.
        $listeAlertes = $this->renvoieAlertesNouvellesOuPrisesEnCompte($filtreAlertesSMA);

        // On ajoute les alertes prises en compte par l'opérateur, sans ticket
        $alertesQueJaiPrisesEnCompte = $this->renvoieAlertesQueJaiPrisesEnCompte($filtreAlertesSMA);
   
        // Merge sachant qu'il y a normalement plus d'alertes nouvelles que d'alertes
        // en cours de prises en compte par l'utilisateur
        $tmp = array();
        foreach($listeAlertes as $alerte) {
            $tmp[$alerte->id] = $alerte;
        }
        unset($listeAlertes);

        foreach($alertesQueJaiPrisesEnCompte as $monAlerte) {
            if (!array_key_exists($monAlerte->id, $tmp)) {
                $tmp[$monAlerte->id] = $monAlerte;
            }
        }
        $alertes = array_values($tmp);
        $this->creationDonneesDynamiques($alertes);

        // On définit un filtrage
        $this->jqGridService->registerCallback(
            "onFiltrageTermine",
            function (&$allData, &$fields) {
                AlerteDTO::onFiltrageTermine($allData, $fields);
            }
        );

        // On met en oeuvre le tri sur la base des critères métier.
        $localRequest = $_REQUEST;
        $localRequest["sidx"] = str_replace('operateur asc, ', 'operateur desc, etat desc, ', $localRequest["sidx"]);
        $retour = $this->jqGridService->createResponseFromModels(
                        $alertes,
                        $localRequest,
                        array(),
                        TRUE,
                        array('date', 'dateBascule'),
                        array('operateur','SORT_DESC'),
                        array('date', 'dateBascule')
        );
        return $retour;
    }


    /**
     * Fournit la liste des alertes en cours de traitement au format JqGrid
     */
    public function getListeEnCoursTraitement() {

        // Construction de la requête
        $this->parseParametres();

        $alertes = $this->renvoieAlertesEnCoursDeTraitement();

        $this->creationDonneesDynamiques($alertes);

        // On définit un filtrage
        $this->jqGridService->registerCallback(
            "onFiltrageTermine",
            function (&$allData, &$fields) {
                AlerteDTO::onFiltrageTermine($allData, $fields);
            }
        );
        $retour = $this->jqGridService->createResponseFromModels($alertes, $_REQUEST, array(), TRUE);

        $alertesAvecTicketSansOp = array(1 => array(), 2 => array());
        $alertesAvecTicketAvecMoiOp = array(1 => array(), 2 => array());
        $alertesAvecTicketAvecOp = array(1 => array(), 2 => array());
        $alertesSansTicket = array(1 => array(), 2 => array());
        $alertesNonDefinitives = array(1 => array(), 2 => array());
        $alertesTraitee = array(1 => array(), 2 => array());
        $autres = array(1 => array(), 2 => array());

        $retourJqgrid = json_decode($retour);
        $rows = $retourJqgrid->rows;

        foreach($rows as $ligne) {
            /*
            // Alerte traitée
            if($ligne->cell[AlerteDTO::idx_statutAlerte] === Alerte::TRAITEE) {
                $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesTraitee;
                $alertesTraitee[] = $ligne;
                continue;
            }*/

            // BZ 145814: il faut toujours les critiques en haut et ce avec n'importe quel tri:
            // On cré un tableau critique et un warning et on merge le tout à la main (au lieu d'un seul par rubrique)

            // Gestion du bloc des alertes SIGNALEE
            if($ligne->cell[AlerteDTO::idx_statutAlerte] == Alerte::SIGNALEE) {

                // Alerte avec n°ticket et pas d'opérateur
                if($ligne->cell[AlerteDTO::idx_ticket] !== NULL && $ligne->cell[AlerteDTO::idx_nom] === NULL) {
                    $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesAvecTicketSansOp;
                    $alertesAvecTicketSansOp[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
                    continue;
                }
                // Alerte avec n°ticket et opérateur = moi
                if($ligne->cell[AlerteDTO::idx_ticket] !== NULL && $ligne->cell[AlerteDTO::idx_operateur] == 1) {
                    $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesAvecTicketAvecMoiOp;
                    $alertesAvecTicketAvecMoiOp[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
                    continue;
                }
                // Alerte avec n°ticket et autre opérateur
                if($ligne->cell[AlerteDTO::idx_ticket] !== NULL && $ligne->cell[AlerteDTO::idx_operateur] == 0 ) {
                    $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesAvecTicketAvecOp;
                    $alertesAvecTicketAvecOp[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
                    continue;
                }

            } // Gestion du bloc des alertes NOUVELLES
            else if($ligne->cell[AlerteDTO::idx_statutAlerte] == Alerte::NOUVELLE) {
                // Alerte sans n°ticket
                $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesSansTicket;
                $alertesSansTicket[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
                continue;
            }
            else if($ligne->cell[AlerteDTO::idx_statutAlerte] == Alerte::INCUBATION) {
                // Gestion du bloc des non définitives
                $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::alertesNonDefinitives;
                $alertesNonDefinitives[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
                continue;
            }

            $ligne->cell[AlerteDTO::idx_operateur] = AlerteDTO::autres;
            $autres[$ligne->cell[AlerteDTO::idx_etat]][] = $ligne;
        }

        $rows = array_merge(    $alertesAvecTicketSansOp[2],
                                $alertesAvecTicketSansOp[1],
                                $alertesAvecTicketAvecMoiOp[2],
                                $alertesAvecTicketAvecMoiOp[1],
                                $alertesAvecTicketAvecOp[2],
                                $alertesAvecTicketAvecOp[1],
                                $alertesSansTicket[2],
                                $alertesSansTicket[1],
                                $alertesNonDefinitives[2],
                                $alertesNonDefinitives[1],
                                //$alertesTraitee,
                                $autres[2],
                                $autres[1]
                            );

        $retourJqgrid->rows = $rows;
        $retour = json_encode($retourJqgrid);

        return $retour;
    }

    /**
     * Fournit la liste complète des alertes quelquesoit l'ancienneté et l'état
     */
    public function getHistorique() {

        // Construction de la requête
        $this->parseParametres();

        $alertes = $this->renvoieToutesLesAlertes();
        $this->creationDonneesDynamiques($alertes);

        $this->jqGridService->registerCallback(
            "onFiltrageTermine",
            function (&$allData, &$fields) {
                AlerteDTO::onFiltrageTermine($allData, $fields);
            }
        );

        $retour = $this->jqGridService->createResponseFromModels($alertes, $_REQUEST, array(), TRUE);

        return $retour;
    }

    public function postCommentaire() {
        // Recoit ids ===> liste d'ids de(s) alerte(s) à commenter
        $listeIds = Input::get("ids");
        // Response: liste d'ids pour lesquels on a ajouté un commentaire
        $listeIdsRetour = array();
        $listeAlertes = Alerte::whereIn("id", $listeIds)->get();
        $commentTxt = Input::get("commentaire");
        
        if (count($listeAlertes) > 0) {
            foreach ($listeAlertes as $alerte) {
                $this->ajouteCommentaire($alerte->id, $commentTxt);
                $listeIdsRetour[] = $alerte->id;
            }
        }
        $retour = $this->jsonService->createResponse($listeIdsRetour);
        return $retour;
    }

    /**
     * Ajoute un commentaire à l'alerte
     * @param int    $alerteId   Id de l'alerte
     * @param string $commentTxt Commentaire
     */

    private function ajouteCommentaire($alerteId, $commentTxt) {

        $alerte = Alerte::where("id", $alerteId)->first();

        if ( $alerte !== NULL) {
            $commentaire = new Commentaire();
            $commentaire->commentaire = urldecode($commentTxt);
            $commentaire->date = $this->timeService->now();
            $commentaire->loginAuteurDuCommentaire = SinapsApp::utilisateurCourant()->login;

            $alerte->commentaires()->save($commentaire);
            return $alerte->id;
        }
        return NULL;
    }

    public function postAcquitter() {
        // Recoit ids ===> id de l'alerte à acquitter
        // Response: ids acquittés
        $alerteId = Input::get("ids");
        $niveau = Input::get("niveau");

        $alerte = Alerte::where("id", $alerteId)->first();

        if ( $alerte !== NULL ) {
            $alerte->loginUtilisateurEnCharge = SinapsApp::utilisateurCourant()->login;
            $alerte->datePriseEnCompte = $this->timeService->now();
            if($niveau === "N0") {
                // Cas où un N1 a pris en compte une alerte avant un N0
                if($alerte->statutAlerte > Alerte::NOUVELLE) {
                    $retour = $this->jsonService->createErrorResponse("200", "L'alerte a été prise en compte par un autre utilisateur.");
                    return $retour;
                }
                $alerte->statutAlerte = Alerte::PRISE_EN_COMPTE;
            }
            $alerte->save();
            // On ajoute un commentaire
            $commentaire = vsprintf(
                'L\'alerte a été prise en compte par %s.',
                array(SinapsApp::utilisateurCourant()->nom)
            );
            $this->ajouteCommentaire($alerte->id, $commentaire);
        }
        $retour = $this->jsonService->createResponse($alerteId);
        return $retour;
    }

    /**
     * Recherche nombre d'alertes sur un numéro d'incident (LIKE).
     *
     * Renvoie le nombre de résolues et non résolues
     * @return type Liste d'objets "Alerte"
     */
    public function nombreAlertesNumeroIncident() {
        $ticket = Input::get("numero_incident");
        $listeAlertes = Alerte::where('ticket', 'LIKE', "%$ticket%")->get();
        $retour = new stdClass();
        $retour->enCours = 0;
        $retour->resolues = 0;
        foreach ($listeAlertes as $alerte) {
            ((string) $alerte->statutAlerte ===  (string) Alerte::RESOLUE) ? $retour->resolues++ : $retour->enCours++;
        }
        $retour = $this->jsonService->createResponse($retour);
        return $retour;
    }

    /**
     * Mise à jour du numéro d'incident pour l'alerte concerné
     * @return type
     */

    public function postNumeroIncident() {
        $alerteId = Input::get("id");
        $ticket = Input::get("numero_incident");
        $razOperateur = Input::get("razOperateur");

        $alerte = Alerte::where("id", $alerteId)->first();
        if ($alerte !== NULL) {
            // On sauvegarde le numero d'incident
            // SAUF SI CELUI-CI EST DEJA LE MEME
            if ($alerte->ticket !== $ticket) {
                $alerte->ticket = urldecode($ticket);

                if($razOperateur) {
                    $alerte->loginUtilisateurEnCharge = NULL;
                } else {
                    $alerte->loginUtilisateurEnCharge = SinapsApp::utilisateurCourant()->login;
                }
                $alerte->datePriseEnCompte = $this->timeService->now();
                $alerte->statutAlerte = Alerte::SIGNALEE;
                $alerte->save();
                // On ajoute un commentaire
                $commentaire = vsprintf(
                    'Le numéro de fiche incident a été positionné à la valeur %s.', array($ticket)
                );
                $this->ajouteCommentaire($alerte->id, $commentaire);
            }
        }
        $retour = $this->jsonService->createResponse($alerteId);
        return $retour;
    }

    public function postResoudre() {
        // Recoit ids ===> liste d'ids de(s) alerte(s) à résoudre
        $listeIds = Input::get("ids");
        // Response: liste d'ids resolus
        $listeIdsRetour = array();
        $listeAlertes = Alerte::whereIn("id", $listeIds)->get();

        $maintenant = $this->timeService->now();
        
        if (count($listeAlertes) > 0) {
            foreach ($listeAlertes as $alerte) {
                
                $alerte->statutAlerte = Alerte::RESOLUE;
                $alerte->save();
                // On ajoute un commentaire
                $commentaire = vsprintf(
                    'L\'alerte a été marquée comme résolue le %s par %s.',
                    array(date("d/m H:i:s", $maintenant),
                    SinapsApp::utilisateurCourant()->nom)
                );

                $this->ajouteCommentaire($alerte->id, $commentaire);

                // On ajoute à l'entrée collectesAForcerSuiteResolu les infos pour trouver les collectes associées
                // Cette entrée servira aussi au moteur pour ne pas calculer l'IE
                $applicationDeLAlerte = $alerte->nomCompletIndicateurEtatDeclencheur;
                $applicationDeLAlerte = explode('.', $alerte->nomCompletIndicateurEtatDeclencheur);
                $applicationDeLAlerte = array_shift($applicationDeLAlerte);

                $contenuAMettre = array('equipement' => '', 'etiquette' => '' );
                $ie = IndicateurEtat::where('nomComplet', $alerte->nomCompletIndicateurEtatDeclencheur)->first();
                if( $ie != NULL ) {
                    $eq = $ie->porteurDIndicateur->equipement;
                    if( $eq !== NULL ) {
                        $contenuAMettre['equipement'] = $eq->fqdn;
                        $contenuAMettre['nomComplet'] = $ie->nomComplet;

                        foreach($ie->tags as $indDuTag) {
                            $etiquette = $indDuTag->tag;
                            if($etiquette !== NULL) {
                                $etiquette = $etiquette->nom;
                                $etiquette = explode('-', $etiquette);
                                array_shift($etiquette);
                                $contenuAMettre['etiquette'] = join('-', $etiquette);
                            }
                        }

                        $ser = serialize($contenuAMettre);

                        $collectesAForcer = SinapsMemcache::get($applicationDeLAlerte. ".collectesAForcerSuiteResolu");
                        if($collectesAForcer === FALSE ) {
                            $collectesAForcer = array();
                            $tableauCompare = array();
                        } else {
                            $collectesAForcer = $collectesAForcer->valeur;
                            foreach( $collectesAForcer as $collecte ) {
                                $tableauCompare[] = serialize($collecte);
                            }
                        }

                        if(!in_array($ser, $tableauCompare)) {
                            $collectesAForcer[] = $contenuAMettre;
                        }

                    }

                    // Si aucune collecte à forcer
                    if(isset($collectesAForcer)) {
						SinapsMemcache::set($applicationDeLAlerte. ".collectesAForcerSuiteResolu", $collectesAForcer, 0);

						// BZ 145117: On positionne l'entrée IEEnAttente + force l'état à "EN ATTENTE" dans le memcache pour l'IE concerné
						$this->collecteurDemandeService->creationListeIEEnAttente($alerte->nomCompletIndicateurEtatDeclencheur);
					}
                }
                $listeIdsRetour[] = $alerte->id;
            }
        }
        $retour = $this->jsonService->createResponse($listeIdsRetour);
        return $retour;
    }

    public function getCommentaires($matcher) {
        $alerteId = $matcher[1];
        // @TODO: utiliser with
        $commentaires = Commentaire::where("Alerte_id", $alerteId)
                                    ->orderBy("date", "DESC")
                                    ->orderBy("id", "DESC")
                                    ->get();

        $result = array();
        foreach ( $commentaires as $commentaire) {

            $result[] = array(
                "date" => $this->dateService->timeToFullDate($commentaire->date),
                "commentaire" => $commentaire->commentaire,
                "utilisateur" => $commentaire->loginAuteurDuCommentaire
            );
        }
        $retour = $this->jsonService->createResponseFromArray($result);
        return $retour;
    }

    protected function handleException(SinapsException $exc) {
        App::abort($exc->getCode());
    }

    public function postSuspendre() {
        // Recoit ids ===> liste d'ids de(s) alerte(s) à suspendre
        $listeIds = Input::get("ids");
        // Response: liste d'ids suspendus
        $listeIdsRetour = array();
        $listeAlertes = Alerte::whereIn("id", $listeIds)->get();

        $dateFinSuspension = Input::get("dateFinSuspension");
        $maintenant = $this->timeService->now();

        if (count($listeAlertes) > 0) {
            foreach ($listeAlertes as $alerte) {
                
                // l'alerte passe à l'état 1 = "Suspendue"
                $alerte->statutAlerte = Alerte::SUSPENDUE;
                // mémorisation de la valeur de dateFinSuspension dans la Base au format timestamp
                $alerte->dateFinSuspension = $this->dateService->allFormatDateToTimestamp($dateFinSuspension, "lt");
                $alerte->save();
                // On ajoute un commentaire
                $commentaire = vsprintf(
                    'L\'alerte a été suspendue le %s jusqu\'au %s par %s.',
                    array(
                        date("d/m H:i:s", $maintenant),
                        $dateFinSuspension,
                        SinapsApp::utilisateurCourant()->nom
                    )
                );
                $this->ajouteCommentaire($alerte->id, $commentaire);
                $listeIdsRetour[] = $alerte->id;
            }
        }
        $retour = $this->jsonService->createResponse($listeIdsRetour);
        return $retour;
    }

    public function postIgnorer() {
        // Recoit ids ===> liste d'ids de(s) alerte(s) à ignorer
        $listeIds = Input::get("ids");
        // Response: liste d'ids ignorées
        $listeIdsRetour = array();
        $listeAlertes = Alerte::whereIn("id", $listeIds)->get();
        $maintenant = $this->timeService->now();

        if (count($listeAlertes) > 0) {
            foreach ($listeAlertes as $alerte) {
                $alerte->statutAlerte = Alerte::IGNOREE;
                $alerte->save();
                // On ajoute un commentaire
                $commentaire = vsprintf(
                    'L\'alerte a été marquée comme ignorée le %s par %s.',
                    array(
                        date("d/m H:i:s", $maintenant),
                        SinapsApp::utilisateurCourant()->nom
                    )
                );
                $this->ajouteCommentaire($alerte->id, $commentaire);
                $listeIdsRetour[] = $alerte->id;
            }
        }
        $retour = $this->jsonService->createResponse($listeIdsRetour);
        return $retour;
    }

    public function postReactiver() {
        // Recoit ids ===> liste d'ids de(s) alerte(s) à réactiver
        $listeIds = Input::get("ids");
        // Response: liste d'ids réactivées
        $listeIdsRetour = array();
        $listeAlertes = Alerte::whereIn("id", $listeIds)->get();
        $maintenant = $this->timeService->now();

        if (count($listeAlertes) > 0) {
            foreach ($listeAlertes as $alerte) {
                $alerte->statutAlerte = Alerte::NOUVELLE;
                $alerte->save();
                // On ajoute un commentaire
                $commentaire = vsprintf(
                    'L\'alerte a été réactivée le %s par %s.',
                    array(
                        date("d/m H:i:s", $maintenant),
                        SinapsApp::utilisateurCourant()->nom
                    )
                );
                $this->ajouteCommentaire($alerte->id, $commentaire);
                $listeIdsRetour[] = $alerte->id;
            }
        }
        $retour = $this->jsonService->createResponse($listeIdsRetour);
        return $retour;
    }

    public function postTraiter() {
        // Recoit ids ===> id de l'alerte à marquer comme traitée
        // Response: ids ignoré
        $alerteId = Input::get("ids");
        $alerte = Alerte::where("id", $alerteId)->first();
        $maintenant = $this->timeService->now();

        if ($alerte !== NULL) {
            $alerte->statutAlerte = Alerte::TRAITEE;
            $alerte->save();
            // On ajoute un commentaire
            $commentaire = vsprintf(
                'L\'alerte a été marquée comme traitée le %s par %s.',
                array(
                    date("d/m H:i:s", $maintenant),
                    SinapsApp::utilisateurCourant()->nom
                )
            );
            $this->ajouteCommentaire($alerte->id, $commentaire);
        }
        $retour = $this->jsonService->createResponse($alerteId);
        return $retour;
    }

    public function creationDonneesDynamiques(&$alertes) {
        $this->createDureeIncubation($alertes);
        $this->creationValeursColonnesCachables($alertes);
    }

    public function createDureeIncubation(&$alertes) {

        foreach($alertes as &$alerte) {

            /** BZ 139291
             * Si l'alerte a un statut différent de "INCUBATION", on l'affiche comme définitive
             */
            if($alerte->statutAlerte !== (string) Alerte::INCUBATION) {
                $alerte->dureeIncubation = 0;
            } else {
                // BZ 138180 - [IHMR] Affichage de la valeur "Au bout de" erronée.
                $alerte->dureeIncubation = $alerte->maxWait*60;

                if($alerte->dureeIncubation !== 0) {
                    // On soustrait l'heure de maintenant à l'heure de levée de l'alerte pour avoir
                    // une durée d'incubation dynamique
                    $dureeIncubationRestante = $alerte->dureeIncubation - ($this->timeService->now() - $alerte->date);
                    if($dureeIncubationRestante <= 0 ) {
                        $alerte->dureeIncubation = 1;
                    } else {
                        $alerte->dureeIncubation = round($dureeIncubationRestante, 2);
                    }
                }
            }
        }
    }

    public function creationValeursColonnesCachables( &$alertes) {

        foreach($alertes as &$alerte) {
            $nomExplose = explode('.', $alerte->nomComplet);
            $alerte->nomIndicateur = array_pop($nomExplose);
            $alerte->nomApplication = array_shift($nomExplose);
            if(count($nomExplose) > 0)
                $alerte->nomGroupeDEquipement = array_shift($nomExplose);
            if(count($nomExplose) > 0)
                $alerte->nomEquipement = array_shift($nomExplose);
            if($alerte->nomEquipement) {
                $alerte->nomCourt = $alerte->nomEquipement . "." . $alerte->nomIndicateur;
            } else {
                $alerte->nomCourt = $alerte->nomIndicateur;
            }

        }
    }

    /**
     * renvoieRequeteSQL.
     *
     * Construit la requête de sélection :
     *          - Clause SELECT
     *          - Filtrage spécifique en fonction de la vue
     *          - Filtrage sur les ids d'application
     *          - Clause ORDER
     *
     * @param array $filtres Tableau contenant des filtres à ajouter à la requête
     * @return string la requête SQL à exécuter
     */

    private function renvoieStatementSQL(array $filtres) {

        $filtrageJQGrid = $this->jqGridService->getSqlFiltreJqGrid(
                    $this->tableauCorrespondance,
                    $this->jqgridFilter,
                    array(  'valeur',
                            'dureeIncubation',
                            'nomApplication',
                            'nomGroupeDEquipement',
                            'nomEquipement',
                            'nomCourt',
                            'nomIndicateur'),
                    array('date', 'dateBascule')
                    );

        if (!empty($this->listeApplications)) {
            $filtres[] = " app.id IN (0, " . join(",", $this->listeApplications) . ") ";
        /*    $filtrageApplication = array();
            $filterApps = Application::whereIn("id", $this->listeApplications)->get();
            foreach($filterApps as $app) {
                $filtrageApplication[] = 'alerte."nomCompletIndicateurEtatDeclencheur" LIKE \'' . OrmQuery::escapeJokers($app->nom) . '.%\'';
            }
            $filtres[] = "(" . join(" OR ", $filtrageApplication) . ")";/*/
        } else {
            $filtres[] = "FALSE";
        }

        // filtrageJQGrid contient déjà son AND
        $sqlQuery = self::SQL_CLAUSE_SELECT . implode(' AND ', $filtres) . ' '
            . $filtrageJQGrid . self::SQL_CLAUSE_ORDER;

        //$sqlQuery = str_replace('__LOGIN__', SinapsApp::utilisateurCourant()->login, $sqlQuery);
//print $sqlQuery;
        $stmt = $this->dbh->prepare($sqlQuery);

        // print "<pre>$sqlQuery</pre><hr>";
        $stmt->bindValue('loginutilisateur', SinapsApp::utilisateurCourant()->login);
        $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, "AlerteDTO");
        return $stmt;
    }



/**
 * Clause SELECT commune
 * Remonte tous les champs nécessaires et suffisant pour afficher une alerte
 * Aucun filtrage
 */
const SQL_CLAUSE_SELECT = <<<EOF
SELECT
        alerte.id as id,
        CASE derog."genConsigne" WHEN NULL THEN NULL
            ELSE derog.consigne
        END AS consigne,
        alerte."nomCompletIndicateurEtatDeclencheur" as "nomComplet",
        '' as "nomCourt",
        '' as "nomApplication",
        '' as "nomGroupeDEquipement",
        '' as "nomEquipement",
        '' as "nomIndicateur",
        alerte."typeAlerte",
        extract(epoch from alerte."dateCreation") as date,
        extract(epoch from alerte."dateBasculeNouvelle") as "dateBascule",
        alerte.etat,
        CONCAT(to_char( alerte."datePriseEnCompte", 'dd/mm H:i:s') ,'<br />', utilisateur.nom) as nom,
        to_char( alerte."datePriseEnCompte", 'dd/mm H:i:s') as "datePriseEnCompte",
        alerte.ticket,
	    array_agg(
			  CONCAT(
				  CONCAT('Nom opérateur: ',commentaire."loginAuteurDuCommentaire"),
				  CONCAT('\nDate: ',to_char(commentaire.date, 'dd/mm H:i:s')),
				  CASE commentaire.commentaire WHEN NULL THEN ''
					  ELSE CONCAT('\n',commentaire.commentaire,'\n')
				  END,
				  CASE commentaire."messageAlerte" WHEN NULL THEN ''
					  ELSE CONCAT('\nAlerte: ',commentaire."messageAlerte",'\n')
				  END,
				  CASE commentaire."donneesConstitutives" WHEN NULL THEN ''
					  ELSE CONCAT('\nDonnées constitutives: ',commentaire."donneesConstitutives",'\n')
				  END,
				  CASE commentaire.consigne WHEN NULL THEN ''
					  ELSE CONCAT('\n',commentaire.consigne,'\n')
				  END,
				  CONCAT('\n',commentaire."destinataireAlerte",'\n')
				  
			  ) ORDER BY commentaire.id DESC, ',' ) as commentaire,
        0 as "dureeIncubation",
        alerte."statutAlerte",       
        COALESCE(ie.id,0) AS "ieId",
        CASE alerte."loginUtilisateurEnCharge" WHEN :loginutilisateur THEN 1
            ELSE 0
        END AS operateur,
        COALESCE(derog."maxWait", COALESCE(ie."maxWait",0)) as "maxWait",
--		CASE derog."maxWait" WHEN NULL THEN
-- COALESCE(ie."maxWait",0)		  
--  CASE ie."maxWait" WHEN NULL THEN '0'
--              ELSE ie."maxWait"::text
--            END
--            ELSE derog."maxWait"
--        END AS "maxWait",
        ie."creationFicheObligatoire" AS "creationFicheObligatoire"
    FROM
        "Alerte" alerte
            LEFT JOIN "IndicateurEtat" ie ON alerte."nomCompletIndicateurEtatDeclencheur"=ie."nomComplet"
            LEFT JOIN "PorteurDIndicateur" pdi ON ie."PorteurDIndicateur_id" = pdi.id
            LEFT JOIN "Application" app ON pdi."Application_id" = app.id
            LEFT JOIN "FicheIncidentCreation" fic ON alerte.id = fic."Alerte_id"
            LEFT JOIN "Derogation" derog on alerte."nomCompletIndicateurEtatDeclencheur"=derog."nomCompletIndicateurEtat"
            LEFT JOIN "Utilisateur" utilisateur ON alerte."loginUtilisateurEnCharge"=utilisateur.login
            JOIN "Commentaire" commentaire on alerte.id = commentaire."Alerte_id",
        "Utilisateur" "userConnect" LEFT JOIN "UtilisateurDuGroupe" utilisateurgpe ON "userConnect".id = utilisateurgpe."Utilisateur_id"
           JOIN "Groupe" gpe ON utilisateurgpe."Groupe_id" = gpe.id
    WHERE
        "userConnect".login = :loginutilisateur AND 
EOF;

/**
 * Clause WHERE 'LES ALERTES GERES PAR L'UTILISATEUR'
 * Vient en complément de la requête de base "self::SQL_CLAUSE_SELECT"
 */
const SQL_WHERE_ALERTES_DE_UTILISATEUR = <<<EOF
    utilisateur.login = alerte."loginUtilisateurEnCharge" 
EOF;

const SQL_WHERE_ALERTES_PRISES_EN_COMPTE = <<<EOF
    alerte."statutAlerte" = 2
EOF;

/**
 * Clause WHERE pour la sélection des nouvelles alertes
 * Vient en complément de la requête de base "self::SQL_CLAUSE_SELECT"
 * alerte.estResolue remplacée par alerte.statutAlerte
 */
const SQL_WHERE_ALERTES_NOUVELLES_OU_PRISES_EN_COMPTE = <<<EOF
    (alerte."statutAlerte" = 0 OR alerte."statutAlerte" = 2)
EOF;

const SQL_WHERE_ALERTES_EN_COURS = <<<EOF
        (alerte."statutAlerte" = 0 OR
        alerte."statutAlerte" = 3 OR
        alerte."statutAlerte" = 10)
EOF;

const SQL_WHERE_AEC_FILTRE = <<<EOF
        (alerte."statutAlerte" = 0 OR
        alerte."statutAlerte" = 3 OR
        alerte."statutAlerte" = 10) AND
        alerte."destinataireAlerte" LIKE CONCAT(gpe.nom,'%')
EOF;

/**
 * Clause ORDER de la requête SELECT
 */
const SQL_CLAUSE_ORDER = <<<EOF
    GROUP BY alerte.id, derog.consigne, derog."genConsigne", utilisateur.nom, ie.id, derog."maxWait"
    ORDER BY alerte.id DESC
    LIMIT 1000;
EOF;


}
