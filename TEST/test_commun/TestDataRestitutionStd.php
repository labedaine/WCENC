<?php

class TestDataRestitutionStd {
    static public function populate($besoinDHistorique=FALSE) {
        // on vide la base
        Utils::truncateAll();

        // Utilisateurs
        $userAdmin = new Utilisateur();
        $userAdmin->nom = "admin";
        $userAdmin->prenom = "admin";
        $userAdmin->login = "admin";
        $userAdmin->password = "admin";
        $userAdmin->email = "admin@";
        $userAdmin->save();

        $groupe = new Groupe();
        $groupe->nom = "ADMIN";
        $groupe->save();

        $utilGpe = new UtilisateurDuGroupe();
        $utilGpe->Utilisateur_id = $userAdmin->id;
        $utilGpe->Groupe_id = $groupe->id;
        $utilGpe->save();

        // Utilisateur technique SINAPS
        $userSPV = new Utilisateur();
        $userSPV->nom = "sinaps";
        $userSPV->prenom = "";
        $userSPV->login = "sinaps";
        $userSPV->password = "ZERKRHJZELKRHZAELRKJHAZERZLKHERLKZAERHZALKERHZERLKZEHRZLKAERHZALKERHZ";
        $userSPV->email = "not_valid";
        $userSPV->save();

        // Collecteur
        $collecteur = new Collecteur();
        $collecteur->hostname = "spvpco1";
        $collecteur->ipv4 = "10.10.10.10";
        $collecteur->save();

        // Domaines et MacroDomaines
        $macroDomaine = new MacroDomaine();
        $macroDomaine->nom = "Test_MD";
        $macroDomaine->save();
        
        $domaine = new Domaine();
        $domaine->nom = "Test_Domaine";
        $macroDomaine->domaines()->save($domaine);
       
        $groupe = new Groupe();
        $groupe->nom = "luigi";
        $groupe->save();
        $luigi=$groupe->id;
        $groupe = new Groupe();
        $groupe->nom = "mario";
        $groupe->save();
        $mario=$groupe->id;
        $groupe = new Groupe();
        $groupe->nom = "peatch";
        $groupe->save();
        $peatch=$groupe->id;
        $groupe = new Groupe();
        $groupe->nom = "george";
        $groupe->save();
        $george=$groupe->id;
 
        // Applications
        $appliSinaps = new Application();
        $appliSinaps->nom = "Sinaps";
        $appliSinaps->equipe_prox = "bobby";
        $appliSinaps->exploitant_app = $george;

        $appliSinaps->exploitant_sys = $luigi;
        $appliSinaps->moe = $mario;
        $appliSinaps->moa = $peatch;
        $domaine->applications()->save($appliSinaps);

        $appliTeleIR = new Application();
        $appliTeleIR->nom = "TeleIR";
        $domaine->applications()->save($appliTeleIR);

        // Profil
        $profilMOE = new Profil();
        $profilMOE->nom = "ADMIN";
        $profilMOE->niveau = 4;
        $profilMOE->save();

        $profilG2A = new Profil();
        $profilG2A->nom = "G2A";
        $profilG2A->niveau = 2;
        $profilG2A->save();

        // Droits de admin
        $profilDeAdmin = new ProfilDeLUtilisateur();
        $profilDeAdmin->__set('Application_id', $appliSinaps->id);
        $profilDeAdmin->Utilisateur_id = $userAdmin->id;
        $profilDeAdmin->Profil_id = $profilMOE->id;
        $profilDeAdmin->save();

        $profilDeAdmin = new ProfilDeLUtilisateur();
        $profilDeAdmin->Application_id = $appliTeleIR->id;
        $profilDeAdmin->Utilisateur_id = $userAdmin->id;
        $profilDeAdmin->Profil_id = $profilG2A->id;
        $profilDeAdmin->save();

        // Grp Equip, Equip, IE
        $groupeEq = new GroupeDEquipement();
        $groupeEq->nom = "Groupe1_Sinaps";
        $appliSinaps->groupesDEquipement()->save($groupeEq);

        $equipement = new Equipement();
        $equipement->fqdn = "spvpbdb2";
        $equipement->ipv4 = "192.168.56.20";
        $equipement->Collecteur_id = $collecteur->id;
        $groupeEq->equipements()->save($equipement);

        // **************************
        // *** Indicateurs d'état ***
        // **************************
        for($i = 0; $i < 10; $i++) {
            $ie[$i] = new IndicateurEtat();
            $ie[$i]->nom = "ETAT_LINUX$i";
            $ie[$i]->destinataireAlerte = "ES";
            $ie[$i]->nomComplet = $equipement->porteurDIndicateur->nomComplet . "." . $ie[$i]->nom;
            if ($i < 5)
                $ie[$i]->creationFicheObligatoire = FALSE;
            else
                $ie[$i]->creationFicheObligatoire = TRUE;
            $ie[$i]->libelle = "libelle $i";
            $equipement->porteurDIndicateur->indicateursEtat()->save($ie[$i]);

            // Alertes
            $alerte = new Alerte();
            $alerte->dateCreation = time() - 60;
            $alerte->etat = 2;
            $alerte->nomCompletIndicateurEtatDeclencheur = $ie[$i]->getNomComplet();
            $alerte->loginUtilisateurEnCharge = "sinaps";
            $alerte->destinataireAlerte = "ES";
            $alerte->typeAlerte = Alerte::SINAPS;
            $alerte->save();

            // Commentaire de cette alerte
            $commentaire = new Commentaire();
            $commentaire->date = time() - 60;
            $commentaire->commentaire  = "Alerte: alerte à cause d'une erreur sur une donnée collectée";
            $commentaire->commentaire .= "\nIndicateur d'état concerné:\n". $ie[$i]->getNomComplet();
            $commentaire->commentaire .= "\nDonnées constitutives:\nDC [" . date('d/m H:i', time()) . "]";
            $commentaire->commentaire .= "\nDestinataire: ". $appliSinaps->exploitant_sys . ", ES";
            $commentaire->Alerte_id = $alerte->id;
            $commentaire->loginAuteurDuCommentaire = "sinaps";
            $commentaire->save();
        }

        // ie -> components_data_unique
        for($i = 0; $i < 10; $i++) {
            $dataUnique = new DonneeCollectee();
            $dataUnique->nom = $ie[$i]->getNomComplet();
            $dataUnique->valeur = $i % 3;
            $dataUnique->date = time();
            $dataUnique->save();
        }

        // ****************************
        // *** Indicateurs Calculés ***
        // ****************************
        $ic = array();
        for($i = 0; $i < 5; $i++) {
            $ic[$i] = new IndicateurCalcule();
            $ic[$i]->nom = "nom_IC_$i";
            $ic[$i]->libelle = "Libelle de l'ind.calc $i";
            $ic[$i]->to_riad = 0;
            $ic[$i]->formule = "TEST.DC.$i+TEST.DC." . ($i * 2);

            $groupeEq->porteurDIndicateur->indicateursCalcules()->save($ic[$i]);
        }

        // ie -> components_data_unique
        for($i = 0; $i < 5; $i++) {
            $dataUnique = new DonneeCollectee();
            $dataUnique->nom = $ic[$i]->getNomComplet();
            $dataUnique->valeur = 100 * $i;
            $dataUnique->date = time();
            $dataUnique->save();
        }


        // ****************************
        // *** Indicateur Graphe ******
        // ****************************
        $ig = array();
        $ig[0] = new IndicateurGraphe();
        $ig[0]->nom = "Graphe_DC0_et_DC1";
        $ig[0]->libelle = "Libelle ind.graphe $i";
        $ig[0]->title = "Graphe DC0 et DC1";
        $ig[0]->periode = "12h";
        $ig[0]->stackSeries = 0;
        $ig[0]->fillSeries = 0;
        $ig[0]->optShowBars = 0;
        $ig[0]->optShowPoints = 0;
        $ig[0]->optShowLines = 0;
        $ig[0]->bgImage = "";
        $ig[0]->abscisse_libelle = "";
        $ig[0]->abscisse_echelleDebut = 0;
        $ig[0]->abscisse_echelleFin = 0;
        $ig[0]->abscisse_echelleAuto = 0;
        $ig[0]->ordonne_libelle = "";
        $ig[0]->ordonne_echelleDebut = "";
        $ig[0]->ordonne_echelleFin = "";
        $ig[0]->abscisse_echelleAuto = 1;
        $ig[0]->ordonne_libelle = "bananes";
        $ig[0]->ordonne_echelleDebut = 0;
        $ig[0]->ordonne_echelleFin = 100;
        $ig[0]->ordonne_echelleAuto = 0;

        $equipement->porteurDIndicateur->indicateursGraphe()->save($ig[0]);

        $serie = new Serie();
        $serie->sourceValeur = $equipement->porteurDIndicateur->nomComplet.".TEST.DC.8";
        $serie->libelle = "Chocolatée";
        $serie->couleur = "#ff0000";
        $serie->fill = 1;
        $serie->showLines = 1;
        $serie->showBars = "";
        $serie->showPoints = "";

        $ig[0]->series()->save($serie);

        // 2ieme IG, 3 series
        $ig[1] = new IndicateurGraphe();
        $ig[1]->nom = "Graphe_3_Series";
        $ig[1]->libelle = "Libelle 3 series";
        $ig[1]->title = "Graphe 3 séries";
        $ig[1]->periode = "12h";
        $ig[1]->stackSeries = 0;
        $ig[1]->fillSeries = 0;
        $ig[1]->optShowBars = 0;
        $ig[1]->optShowPoints = 0;
        $ig[1]->optShowLines = 0;
        $ig[1]->bgImage = "";
        $ig[1]->abscisse_libelle = "";
        $ig[1]->abscisse_echelleDebut = 0;
        $ig[1]->abscisse_echelleFin = 0;
        $ig[1]->abscisse_echelleAuto = 0;
        $ig[1]->ordonne_libelle = "";
        $ig[1]->ordonne_echelleDebut = "";
        $ig[1]->ordonne_echelleFin = "";
        $ig[1]->abscisse_echelleAuto = 1;
        $ig[1]->ordonne_libelle = "who knows";
        $ig[1]->ordonne_echelleDebut = 0;
        $ig[1]->ordonne_echelleFin = 100;
        $ig[1]->ordonne_echelleAuto = 0;

        $equipement->porteurDIndicateur->indicateursGraphe()->save($ig[1]);

        for($i = 5; $i < 8; $i++) {
            $serie = new Serie();
            $serie->sourceValeur = $equipement->porteurDIndicateur->nomComplet.".TEST.DC.$i";
            $serie->libelle = "DC.$i";
            $serie->couleur = "#" . rand(0, 999999);
            $serie->fill = "";
            $serie->showLines = 1;

            $ig[1]->series()->save($serie);
        }

        // ************************
        // *** Tables de verite ***
        // ************************
        $table = new TableDeVerite();
        $table->type = TableDeVerite::ET;
        $table->XParmiN = 1;
        $table->IndicateurEtat_id = 1;
        $table->ordre = 1;
        $table->message = "Message de la table de verite 1";
        $table->formule = '[{"name":"Sinaps.Groupe1_Sinaps.spvpbdb2.TEST.DC.0","unknown_comp":"n=",' .
            '"unknown_value":"3","critical_comp":"n=","critical_value":"2","warning_comp":"n=",' .
            '"warning_value":"1","ok_comp":"n=","ok_value":"0"},{"name":"Sinaps.Groupe1_Sinaps.spvpbdb2.TEST.DC.1",' .
            '"unknown_comp":"n=","unknown_value":"3","critical_comp":"n=","critical_value":"2",' .
            '"warning_comp":"n=","warning_value":"1","ok_comp":"n=","ok_value":"0"},' .
            '{"name":"Sinaps.Groupe1_Sinaps.spvpbdb2.TEST.DC.2","unknown_comp":"n=",' .
            '"unknown_value":"3","critical_comp":"n=","critical_value":"2","warning_comp":"n=",' .
            '"warning_value":"1","ok_comp":"n=","ok_value":"0"}]';
        $table->save();

        $table = new TableDeVerite();
        $table->type = TableDeVerite::ET;
        $table->XParmiN = 1;
        $table->IndicateurEtat_id = 1;
        $table->ordre = 2;
        $table->message = "Message de la table de verite 2";
        $table->formule = '[{"name":"Sinaps.Groupe1_Sinaps.spvpbdb2.TEST.DC.3","unknown_comp":"n=",' .
            '"unknown_value":"3","critical_comp":"n=","critical_value":"2","warning_comp":"n=",' .
            '"warning_value":"1","ok_comp":"n=","ok_value":"0"}]';
        $table->save();
        // ************
        // *** Tags ***
        // ************
        $tag = new Tag();
        $tag->nom = "Premier_tag_de_test";
        $tag->save();

        $tag = new Tag();
        $tag->nom = "Deuxieme_tag_de_test";
        $tag->save();

        // ***************************
        // *** Indicateurs de tags ***
        // ***************************
        $indicateurDuTag = new IndicateursDuTag();
        $indicateurDuTag->Tag_id = 1;
        $indicateurDuTag->IndicateurEtat_id = 1;
        $indicateurDuTag->save();

        $indicateurDuTag = new IndicateursDuTag();
        $indicateurDuTag->Tag_id = 1;
        $indicateurDuTag->IndicateurGraphe_id = 1;
        $indicateurDuTag->save();

        $indicateurDuTag = new IndicateursDuTag();
        $indicateurDuTag->Tag_id = 1;
        $indicateurDuTag->IndicateurGraphe_id = 2;
        $indicateurDuTag->save();

        $indicateurDuTag = new IndicateursDuTag();
        $indicateurDuTag->Tag_id = 2;
        $indicateurDuTag->IndicateurGraphe_id = 2;
        $indicateurDuTag->save();

        // ***********************
        // *** Tableau de bord ***
        // ***********************
        $tdb = new TableauDeBord();
        $tdb->nom = "Sinaps 001";
        $tdb->cacherIndicateurs = 0;
        $tdb->cacherCalculs = 0;
        $tdb->cacherGraphes = 0;
        $tdb->cacherTdb = 0;
        $tdb->nbLignes = 3;
        $appliSinaps->tableauxDeBord()->save($tdb);

        for($i = 1; $i <= 3; $i++) {
            $ie_tdb1 = new IEDuTableauDeBord();
            $ie_tdb1->x = $i;
            $ie_tdb1->y = $i;
            $ie_tdb1->nomCompletIE = $ie[$i]->nomComplet;
            $tdb->ieDuTableauDeBord()->save($ie_tdb1);
        }

        for($i = 0; $i < 1; $i++) {
            $ig_tdb1 = new IGDuTableauDeBord();
            $ig_tdb1->x = $i;
            $ig_tdb1->y = 1;
            $ig_tdb1->IndicateurGraphe_id = $ig[$i]->id;
            $tdb->igDuTableauDeBord()->save($ig_tdb1);
        }

        for ($i = 0; $i < 5; $i++) {
            $ic_tdb1 = new ICDuTableauDeBord();
            $ic_tdb1->x = $i;
            $ic_tdb1->IndicateurCalcule_id = $ic[$i]->id;
            $tdb->icDuTableauDeBord()->save($ic_tdb1);
        }

        // **************************
        // *** Données collectées ***
        // **************************
        $dc = array();
        for($i = 0; $i < 20; $i++) {
            $dc[$i] = new DonneeCollectee();
            $dc[$i]->nom = $equipement->getNomComplet() . ".TEST.DC.$i";
            $dc[$i]->valeur = $i;
            $dc[$i]->date = time();
            $dc[$i]->estDonneeCollectee = TRUE;
            $dc[$i]->save();
        }

        // ***************************
        // *** Un peu d'historique ***
        // ***************************
        if ($besoinDHistorique === TRUE) {


            for($i = 60; $i <= 14 * 3600; $i += 60) { // tt les minutes pendant 14h
                $histo = new DonneeHistorisee();
                $histo->date = $dc[8]->date - $i;
                $histo->valeur = 50 + sin($i / 10000) * 50;
                $histo->components_data_unique_id = $dc[8]->id;

                $histo->save();
            }

            for($i = 3600 * 2; $i <= 9 * 3600; $i += 60 * 5) { // tt 5 mins pendant 7h
                $histo = new DonneeHistorisee();
                $histo->date = $dc[5]->date - $i;
                $histo->valeur = ($i % 3000) % 80;
                $histo->components_data_unique_id = $dc[5]->id;

                $histo->save();
            }

            for($i = 3600 * 2; $i <= 9 * 3600; $i += 60 * 5) { // tt 5 mins pendant 7h
                $histo = new DonneeHistorisee();
                $histo->date = $dc[6]->date - $i;
                $histo->valeur = (($i / 7200) % 2) * 80;
                $histo->components_data_unique_id = $dc[6]->id;

                $histo->save();
            }

            for($i = 60; $i <= 18 * 3600; $i += 60 * 1) {
                $histo = new DonneeHistorisee();
                $histo->date = $dc[7]->date - $i;
                $histo->valeur = (($i / 3600) % 2) * 50 + 10;
                $histo->components_data_unique_id = $dc[7]->id;

                $histo->save();
            }

            // Ajoute 1500 lignes pour la DC9 (TESTS de l'histrorique des valeurs)
            for($i = 1; $i <= 2000; $i ++) {
                $histo = new DonneeHistorisee();
                $histo->date = $dc[9]->date - $i;
                $histo->valeur = 50 + sin($i / 10000) * 50;
                $histo->components_data_unique_id = $dc[9]->id;
                $histo->save();
            }

        }
    }
}
