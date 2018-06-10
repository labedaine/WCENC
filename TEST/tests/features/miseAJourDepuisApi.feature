# language: fr
# date du match: 2018-06-14T15:00:00Z
Fonctionnalité: Controle de la mise a jour des scores

    Scénario: Je demande la mise à jour mais aucun match
        Quand il est 16h00:00
        Et je demande la mise à jour via match_no_change
        Alors je n'ai pas de changement
        Et le log contient 0 matchs trouvés

    Scénario: Je demande la mise à jour et j'ai un retour
        Quand il est 17h05:00
        Et je demande la mise à jour via match_no_change
        Alors le log contient 1 matchs trouvés
        Et le match d'id 165069 a le statut TIMED
        Et le match d'id 165069 n'a pas de score

    Scénario: Je demande la mise à jour et j'ai une mise a jour match en cours sans but
        Quand il est 17h05:00
        Et je demande la mise à jour via match_en_cours
        Alors le log contient 1 matchs trouvés
        Et le match d'id 165069 a le statut IN_PLAY
        Et le match d'id 165069 n'a pas de score

    Scénario: Je demande la mise à jour et j'ai une mise a jour match terminé
        Quand il est 17h05:00
        Et je demande la mise à jour via match_termine
        Alors le log contient 1 matchs trouvés
        Et le match d'id 165069 a le statut FINISHED
        Et le match d'id 165069 a le score 2-0
        Et le log contient 0 - 0 => 2 - 0


