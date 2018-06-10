# language: fr
# date du match: 2018-06-14T15:00:00Z
Fonctionnalité: Controle de la mise a jour des points après paris

    Contexte:
        Quand nous sommes le 14-6 16h00:00
        Quand je cré les utilisateurs:
        |   nom | password | isActif | isAdmin |
        |   bob |    bob01 |       0 |       0 |
        |  jack |   jack01 |       1 |       0 |

    Scénario: Aucun pari le score est de 0
        Alors le joueur bob a un score de 0
        Alors le joueur jack a un score de 0

    Scénario: un pari mais pas de résultat encore
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 0 | 0 |
        Quand nous sommes le 14-6 17h00:00
        Et je demande la mise à jour via match_no_change
        Alors le joueur jack a un score de 0

    Scénario: un pari ok
        Quand nous sommes le 14-6 16h05:00
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 1 | 0 |
        Alors le pari de jack est pris en compte
        Quand nous sommes le 14-6 19h05:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 1

    Scénario: un pari mais trop tard
        Quand nous sommes le 14-6 17h05:00
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 1 | 0 |
        Alors le pari de jack n'est pas pris en compte
        Quand nous sommes le 14-6 19h05:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 0

    Scénario: un pari mais pas de résultat encore: match en cours
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 1 | 0 |
        Quand il est 17h00:00
        Et je demande la mise à jour via match_en_cours
        Alors le joueur jack a un score de 0

    Scénario: un pari mais pas avec résultat
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 0 | 0 |
        Quand nous sommes le 14-6 19h00:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 0
        Quand je demande le calcul des score pour le match 165069
        Alors le joueur jack a un score de 0
        Quand je demande le calcul des score pour les utilisateurs
        Alors le joueur jack a un score de 0

    Scénario: un pari mais pas avec résultat ok
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 2 | 0 |
        Quand nous sommes le 14-6 19h00:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 3
        Quand je demande le calcul des score pour le match 165069
        Alors le joueur jack a un score de 3
        Quand je demande le calcul des score pour les utilisateurs
        Alors le joueur jack a un score de 3

    Scénario: un pari mais pas avec résultat ok, deux paris
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 3 | 1 |
        | bob  | 165069 | 3 | 0 |
        Quand nous sommes le 14-6 19h00:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1
        Quand je demande le calcul des score pour le match 165069
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1
        Quand je demande le calcul des score pour les utilisateurs
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1

    Scénario: un pari mais pas avec résultat ok, deux paris puis nouveau paris
    # 165083 | 2018-06-15 17:00:00+02 |           815 |           840 |       2 |
        Quand nous sommes le 14-6 16h00:00
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165069 | 3 | 1 |
        | bob  | 165069 | 3 | 0 |
        Quand nous sommes le 14-6 19h00:00
        Et je demande la mise à jour via match_termine
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1
        Quand je demande le calcul des score pour le match 165069
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1
        Quand je demande le calcul des score pour les utilisateurs
        Alors le joueur jack a un score de 2
        Alors le joueur bob a un score de 1
        Quand nous sommes le 15-6 16h00:00
        Et les paris:
        | login | match | score_dom | score_ext |
        | jack | 165083 | 0 | 1 |
        | bob  | 165083 | 0 | 0 |
        Quand nous sommes le 15-6 18h45:00
        Et je demande la mise à jour via match_termine_165083
        Alors le match d'id 165083 a le statut FINISHED
        Et le match d'id 165083 a le score 0-0
        Et le joueur jack a un score de 2
        Et le joueur bob a un score de 4
