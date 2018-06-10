# language: fr
# date du match: 2018-06-14T15:00:00Z
Fonctionnalité: Controle de la mise a jour des phases finales

    Scénario: Je récupère la phase en cours: groupe
        Quand il est 16h00:00
        Et la phase en cours est la phase de groupe
        Alors la phase en cours a la valeur 1
        Et je demande la mise à jour des phases finales via phase_finale_no_change
        Et le match d'id 165119 n'est pas initialisé

    Scénario: Je récupère la phase en cours: phase finale
        Quand il est 16h00:00
        Et la phase en cours est les phases finales
        Alors la phase en cours a la valeur 4
        Et je demande la mise à jour des phases finales via phase_finale_no_change
        Et le match d'id 165119 n'est pas initialisé

    Scénario: Je récupère la phase en cours: mise a jour match
        Quand il est 16h00:00
        Et la phase en cours est les phases finales
        Alors la phase en cours a la valeur 4
        Et je demande la mise à jour des phases finales via phase_finale_no_change_1_team
        Et le match d'id 165119 se joue entre Russie et Arabie_Saoudite

    Scénario: Je récupère la phase en cours: mise a jour de deux matchs
        Quand il est 16h00:00
        Et la phase en cours est les phases finales
        Alors la phase en cours a la valeur 4
        Et je demande la mise à jour des phases finales via phase_finale_no_change_2_team_2_match
        Et le match d'id 165123 se joue entre Russie et Arabie_Saoudite
        Et le match d'id 165124 se joue entre France et Islande




