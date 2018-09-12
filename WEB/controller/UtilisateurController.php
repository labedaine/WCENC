<?php
/**
 * Gere l'authentification, les droits, la modification des droits.
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 *
 * PATCH_5_09 : Classe Utilisateur propre à IHMR
 */

require_once __DIR__.'/../DTO/UtilisateurDTO.php';

class UtilisateurController extends BaseController {
	
    protected $jqGridService;
    private $jsonService;

    private $utilisateurService;
    private $fileService;
    private $droitsService;
    private $importUtilisateurs;

    public function __construct() {

        $this->jqGridService = SinapsApp::make("JqGridService");
        $this->utilisateurService = App::make("UtilisateurService");
        $this->droitsService = App::make("DroitsService");
        $this->jsonService = App::make("JsonService");
        $this->restClientService = App::make("RestClientService");
        $this->systemService = App::make("SystemService");
        $this->fileService = SinapsApp::make("FileService");

    }
    
    /**
     * Suppression d'un utilisateur
     * @param type $idUtilisateur
     * @return type
     */
    public function supprimerUtilisateur($matcher) {
        try {
            $this->applyFilter("authentification");

            $idUtilisateur = $matcher[1];

            $this->utilisateurService->supprimerUtilisateur($idUtilisateur);
            $retour = $this->jsonService->createResponse($idUtilisateur);

            return $retour;
        } catch(Exception $err) {
            $retour = JsonService::createErrorResponse("500", $err->getMessage());
            return $retour;
        }
    }

    /**
     * Renvoie les informations d'un utilisateur
     */

    public function getDetails($matcher) {
        $userId = $matcher[1];

        $utilisateur = Utilisateur::where('id',$userId)->first();

        if ($utilisateur) {
            // On récupère les préférences
            $listePrefereces = array();
            $preferencesUtilisateur = UtilisateurPreference::where('Utilisateur_id', $userId)->get();
            foreach ($preferencesUtilisateur as $preference) {
                $listePrefereces[] = array(
                    "clef" => $preference->clef,
                    "valeur" => $preference->valeur
                );
            }
            
            $array = array(
                "id" => $utilisateur->id,
                "nom" => $utilisateur->nom,
                "prenom" => $utilisateur->prenom,
                "email" => $utilisateur->email,
                "login" => $utilisateur->login,
                "password" => $utilisateur->password,
                "preferences" => $listePrefereces
            );
            $retour = $this->jsonService->createResponseFromArray($array);
        } else {
            $retour = $this->jsonService->createErrorResponse('601', 'Utilisateur introuvable');
        }
        return $retour;
    }

    const SQL_LISTE_UTILISATEURS = <<<EOF
        SELECT
                "UTL".id,
                "UTL".nom, "UTL".prenom,
                "UTL".login, "UTL".email,
                '' AS groupes,
                CASE WHEN 
                REPLACE(REPLACE(REPLACE(REPLACE(
                    (SELECT 
                        string_agg("PREF".valeur, '<br />')
                        FROM "UtilisateurPreference" AS  "PREF"
                        WHERE "PREF"."Utilisateur_id" = "UTL".id 
                        AND "PREF".valeur IN ('administrateur', 'superviseur','EOM','gestionHabilitations')
                        GROUP BY "PREF".valeur
                        ORDER BY "PREF".valeur
                    ) , 
                'superviseur', 'Superviseur PSN'), 'administrateur', 'Administrateur PSN'), 
                'EOM', 'EOM Sinaps'), 'gestionHabilitations', 'Gestion des habilitations'
                ) IS NOT NULL THEN '' END AS "role"
        FROM "Utilisateur" AS "UTL"
        LEFT JOIN "UtilisateurDuGroupe" AS "UTG" ON "UTG"."Utilisateur_id" = "UTL".id
        LEFT JOIN "Groupe" AS "GRP" ON "UTG"."Groupe_id" = "GRP".id
        WHERE "UTL"."isActif" = 1
        GROUP BY "UTL".login, "UTL".id, "GRP".nom
        ORDER BY "UTL".nom, "GRP".nom
EOF;

}
