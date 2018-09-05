#!/usr/bin/php
<?php
/**
 * Script qui cré un palmares
 *
 * Exporte les points de chaque personne pour une compétition et cré la compétition équivalente
 * 
 */

require_once __DIR__."/../Autoload.php";
require_once __DIR__."/../ressource/php/Autoload.php";

class CreatePalmares extends SinapsScript {

    private $test = FALSE;

    const DEBUT_PHASE_FINALE=4;

    public function __construct() {

        parent::__construct(__DIR__."/../config","CompetitionLogger","competition");
        $this->logger = SinapsApp::make("CompetitionLogger");

        $this->apiController = new ApiFootballDataController();
    }

    public function configure() {
        $conf = $this->setName(__FILE__)
                     ->setDescription("cré le palmares")
					 ->addOption(
                         "libelle",
                         SinapsScript::OBLIGATOIRE,
                         "libellé de la compétition"
                     )->addOption(
                         "createPalmares",
                         SinapsScript::FACULTATIF | SinapsScript::VALUE_NONE,
                         "On cré l'historique depuis la base courante"
                     )->addOption(
                         "force",
                         SinapsScript::FACULTATIF | SinapsScript::VALUE_NONE,
                         "force la création de l'historique "
                     )
                     ->startAlternative()
                     ->addOption(
                         "listeCompetition",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "liste les compétitions existantes"
                     )
                     ->startAlternative()
                     ->addOption(
                         "listePalmares",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "liste les palmares existants"
                     );
    }

    public function performRun() {

        try {
			
			if(isset($this->options->listeCompetition)) {
				$competitions = Competition::all();
				foreach($competitions as $competition) {
					echo $competition->id . " " . $competition->libelle . "\n";
				}
			}
			
			if(isset($this->options->listePalmares)) {
				$palmares = Palmares::all();
				
				foreach($palmares as $palmar) {
					$user = Utilisateur::find($palmar->utilisateur_id);
					echo $palmar->competition . " " . $user->login . " " . $palmar->points . "\n";
				}
			}
			if(isset($this->options->libelle)) {
			
				$competition = Competition::where("libelle", $this->options->libelle);
				if($competition !== NULL) {
					$this->logger->addInfo("La competition de libelle ".$this->options->libelle." existe déjà (utilisez --force pour recréer l'historique)");
				} else {
					$this->logger->addInfo("Création de la compétition de libelle ".$this->options->libelle);
					$competition = new Competition();
					$competition->libelle = $this->options->libelle;
					$competition->save();
				}
				if(isset($this->options->createPalmares)) {
					// Maintenant la compétition existe on peut sauvegarder l'historique dans palmares
					$users = Utilisateur::all();
					foreach($users as $user) {
						// on cherche la ligne de palmares
						$palmares = Palmares::where('utilisateur_id', $user->id)
											 ->where('competition', $this->options->libelle)
											 ->first();
											 
						if($palmares === NULL) {
							$palmares = new Palmares();
						}
						
						$palmares->competition = $this->options->libelle;
						$palmares->points = $user->points;
						$palmares->utilisateur_id = $user->id;
						
						if($palmares !== NULL) {
							if($this->options->force) {
								$palmares->save();
							}
						} else {
							$palmares->save();
						}
					}
					
				}
			}
			

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }
}

$createPalmares = new CreatePalmares();
$createPalmares->run(TRUE);

exit(0);
