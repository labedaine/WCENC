#!/usr/bin/php
<?php
/**
 * Script qui envoie des mails au gens la veille des matchs
 *
 */

require_once __DIR__."/../Autoload.php";
require_once __DIR__."/../ressource/php/Autoload.php";

class SendNotification extends SinapsScript {

    public function __construct() {

        parent::__construct(__DIR__."/../config","CompetitionLogger","competition");
        $this->logger = SinapsApp::make("CompetitionLogger");

        $this->apiController = new ApiFootballDataController();
        $this->mailService = new MailService();
        $this->timeService = new TimeService();
    }

    public function configure() {
        $conf = $this->setName(__FILE__)
                     ->setDescription("cré le palmares")
                     ->addOption(
                         "send",
                         SinapsScript::OBLIGATOIRE | SinapsScript::VALUE_NONE,
                         "Envoie le mail de notification pour les match a venir"
                     );
    }

    public function performRun() {

        try {
            $this->logger->contexte = "NOTIFICATION";
            // On regarde les matchs a venir, en somme le premier
            $match = Match::whereIn('etat_id', array(1,2))
                          ->where('date_match', '>', date("Y-m-d 00:00:00", $this->timeService->now()))
                          ->where('date_match', '<',  date("Y-m-d 00:00:00", $this->timeService->now() + 86400))
                          ->orderBy('date_match')
                          ->first();

            if(!empty($match)) {
                $users = Utilisateur::where('notification' , 1)
                                    ->get();

                $cptUsers = count($users);
                $cpt = 0;
                foreach($users as $user) {
                    // On envoie le mail
                    $this->mailService->envoyerMailMatchAVenir($user->email);
                    $cpt++;
                }

                $this->logger->addInfo("Mail envoyé à $cpt/$cptUsers utilisateurs");
            } else {
                $this->logger->addInfo("Aucun mail envoyé (aucun match demain)");
            }

        } catch( SinapsException $e) {
            $this->logger->contexte = NULL;
            $this->logger->addError($e->getMessage());
        }
    }
}

$sendNotification = new SendNotification();
$sendNotification->run(TRUE);

exit(0);
