<?php

/**
 * Classe chargée de gérer les objets pour écran d'alertes.
 *
 * PHP version 5
 *
 * @author stéphane gatto <stephane.gatto@dgfip.finances.gouv.fr>
 */

class AlerteDTO extends SinapsModel {

    protected $consigne;
    protected $nomComplet;
    protected $nomApplication;
    protected $nomGroupeDEquipement;
    protected $nomCourt;
    protected $nomEquipement;
    protected $nomIndicateur;
    protected $typeAlerte;
    protected $date;
    protected $dateBascule;
    protected $etat;
    protected $nom;
    protected $datePriseEnCompte;
    protected $ticket;
    protected $statutAlerte;
    protected $commentaire;
    protected $dureeIncubation;
    protected $ieId;
    protected $operateur;
    protected $creationFicheObligatoire;
    protected $maxWait;
    protected $astreinte;

    const idx_consigne              = 1;
    const idx_nomComplet            = 2;
    const idx_nomApplication        = 3;
    const idx_nomGroupeDEquipement  = 4;
    const idx_nomCourt              = 5;
    const idx_nomEquipement         = 6;
    const idx_nomIndicateur         = 7;
    const idx_typeAlerte            = 8;
    const idx_date                  = 9;
    const idx_dateBascule           = 10;
    const idx_etat                  = 11;
    const idx_nom                   = 12;
    const idx_datePriseEnCompte     = 13;
    const idx_ticket                = 14;
    const idx_statutAlerte          = 15;
    const idx_commentaire           = 16;
    const idx_dureeIncubation       = 17;
    const idx_ieId                  = 18;
    const idx_operateur             = 19;
    const idx_creationFicheObligatoire = 20;
    const idx_maxWait               = 21;
    const idx_astreinte             = 22;
    const idx_detail_IE             = 23;
    const idx_listeCommentaires     = 24;
    const idx_resoluParSinaps       = 25;

    const alertesAvecTicketSansOp   = "Alerte(s) avec un n° incident renseigné et non prise(s) en compte";
    const alertesAvecTicketAvecMoiOp= "Alerte(s) avec un n° incident renseigné que j'ai prise(s) en compte";
    const alertesAvecTicketAvecOp   = "Alerte(s) avec un n° incident renseigné que d'autres ont prise(s) en compte";
    const alertesSansTicket         = "Alerte(s) définitive(s) sans n° incident renseigné";
    const alertesNonDefinitives	    = "Alerte(s) non définitives(s)";
    const alertesTraitee            = "Alerte(s) traitée(s)";
    const autres                    = "Autre(s) alerte(s)";

    static function onFiltrageTermine( &$rawData, &$fields) {
        if (count($rawData) === 0) {
            return;
        }

        static::appendDetailsIE( $rawData, $fields);
        static::appendListeCompleteCommentaires( $rawData, $fields);
    }

    protected static function appendDetailsIE( &$rawData, &$fields) {
        $fields[] = "details IE";

        // @TODO: utiliser whereIn sur l'IE et with ?
        foreach($rawData as &$rowLine) {
            $ie = IndicateurEtat::find($rowLine["ieId"]);
            if( $ie !== NULL ) {
                $rowLine[] = array( "nomComplet"    => $ie->nom,
                                    "libelle"       => $ie->libelle);
            } else {
                $rowLine[] = array();
            }
        }
    }

    protected static function appendListeCompleteCommentaires( &$rawData, &$fields) {
        $dateService = SinapsApp::make("DateService");
        $fields[] = "commentaires";
        $fields[] = "resoluParSinaps";

        $index = array();

        foreach( $rawData as &$alerte) {
            $alerteId = $alerte["id"];

            $index[$alerteId] = &$alerte;
            $alerte["commentaires"]     = array();
            $alerte["resoluParSinaps"]  = FALSE;
        }

        $commentaires = Commentaire::whereIn( "Alerte_id", array_keys($index))
                                   ->orderBy("date", "DESC")
                                   ->get();

        foreach ( $commentaires as $commentaire) {
            $alerte = &$index[$commentaire->Alerte_id];
            $alerte["commentaires"][] = array(  "date" => $dateService->timeToCommentFormatDate( $commentaire->date),
                                                "commentaire" => $commentaire->commentaire,
                                                "messageAlerte" => $commentaire->messageAlerte,
                                                "donneesConstitutives" => $commentaire->donneesConstitutives,
                                                "consigne" => $commentaire->consigne,
                                                "destinataireAlerte" => html_entity_decode($commentaire->destinataireAlerte),
                                                "utilisateur" => strtoupper($commentaire->getUtilisateur()->nom));

            // On ajout en champs 'resoluParSinaps' true/false
            if($commentaire->commentaire !== NULL) {
                if(preg_match("/alerte a été résolue automatiquement le\s.*\spar SINAPS/", $commentaire->commentaire)) {
                    $alerte["resoluParSinaps"]  = TRUE;
                }
            }
        }
    }

    public function setValeur( $valeur ) {
        $this->valeur = $valeur;
    }
}
