<?php

// @TODO: Add test
class GroupeDTO extends SinapsModel {
    // Le premier champ "id de l'indicateur" est implicite
    // et n'apparait pas dans cette liste
    protected $nom;
    protected $groupeMail;
    protected $groupeTelephone;
    protected $groupeDescription;
    protected $nomSMA;
    protected $nbApplis;
    protected $nbUsers;
    protected $htmlUtilisateurs;
    protected $htmlApplications;

     static function onFiltrageTermine( &$rawData, &$fields) {
        $nbDatas = count($rawData);
        if ($nbDatas === 0) {
            return;
        }
        static::appendDetailsGroupe( $rawData, $fields);
    }

    protected static function appendDetailsGroupe( &$rawData, &$fields) {

        foreach($rawData as &$rowLine) {
            // On recherche l'ensemble des utilisateurs du groupe
            $tabHtmlUtilisateur = "<table>";
            $mesUtilisateursDuGroupe = UtilisateurDuGroupe::where('Groupe_id', $rowLine["id"])->get();
            $cpt = 0;
            
            $tampon = array();
            
            foreach($mesUtilisateursDuGroupe as $utilisateurDuGroupe) {
				$tampon[] = Utilisateur::find($utilisateurDuGroupe->Utilisateur_id);
			}
			
			usort($tampon, function ($a, $b) { return $a->nom > $b->nom;});
						
			foreach($tampon as $utilisateur) {
                $cpt++;
                if($cpt===0) {
                    $tabHtmlUtilisateur .= "<tr>";
                }

                $tabHtmlUtilisateur .= "<td>". $utilisateur->prenom . " " . $utilisateur->nom ."</td><td width=20px></td>";

                if($cpt%4===0) {
                    $tabHtmlUtilisateur .= "</tr><tr>";
                }
            }
			
			if($tabHtmlUtilisateur === "<table>") {
				$tabHtmlUtilisateur = null;
			} else {
				$tabHtmlUtilisateur .= "</tr></table>";
				$tabHtmlUtilisateur = "<label><u><b>Liste des utilisateurs:</b></u></label>" . $tabHtmlUtilisateur;
			}
            
            // On recherche l'ensemble des applications du groupe
            $tabHtmlApplication = "<table>";
            $mesApplicationsDuGroupe = ApplicationDuGroupe::where('Groupe_id', $rowLine["id"])->get();

            $cpt = 0;
            
            $tampon = array();
            
            foreach($mesApplicationsDuGroupe as $applicationDuGroupe) {
				$tampon[] = Application::find($applicationDuGroupe->Application_id);
			}
		$tampon = array_filter($tampon);	
			usort($tampon, function ($a, $b) { return strtoupper($a->nom) > strtoupper($b->nom);});
       
            foreach($tampon as $application) {
                $cpt++;
                if($cpt===0) {
                    $tabHtmlApplication .= "<tr>";
                }

                $tabHtmlApplication .= "<td>". $application->nom ."</td><td width=20px></td>";

                if($cpt%4===0) {
                    $tabHtmlApplication .= "</tr><tr>";
                }
            }
            
			if($tabHtmlApplication === "<table>") {
				$tabHtmlApplication = null;
			} else {
				$tabHtmlApplication .= "</tr></table>";
				$tabHtmlApplication = "<label><u><b>Liste des applications:</b></u></label>" . $tabHtmlApplication;
			}

            $rowLine["htmlUtilisateurs"] = $tabHtmlUtilisateur;
            $rowLine["htmlApplications"] = $tabHtmlApplication;
        }
    }

}
