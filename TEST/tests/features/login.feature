# language: fr
Fonctionnalité: Controle de l'authentification

    Contexte:
        Quand je cré les utilisateurs:
        |   nom | password | isActif | isAdmin |
        |   bob |    bob01 |       0 |       0 |
        |  jack |   jack01 |       1 |       0 |

    Scénario: Je me connecte avec un login/mdp valide, mais non actif 402 je ne peux pas récupérer mes paris
        Quand je me connecte avec bob / bob01
        Et je demande la liste de mes paris
        Alors j'ai un code retour 402
        Et je demande la vue administrateur
        Alors j'ai un code retour 402

    Scénario: Je me connecte avec un mdp invalide, je ne peux pas récupérer mes paris
        Quand je me connecte avec bob / bill
        Alors j'ai un code retour 401
        Et je demande la liste de mes paris
        Alors j'ai un code retour 401
        Et je demande la vue administrateur
        Alors j'ai un code retour 401

    Scénario: Je me connecte avec un login/mdp valide, je peux récupérer mes paris mais pas la vue admin
        Quand je me connecte avec jack / jack01
        Alors je n'ai pas d'erreur http
        Et je demande la liste de mes paris
        Alors je n'ai pas d'erreur http
        Et je demande la vue administrateur
        Alors je n'ai pas d'erreur http
        Et j'ai un access denied

    Scénario: Je me connecte puis je me deconnecte, j'ai une erreur d'authentification
        Quand je me connecte avec jack / jack01
        Et je demande la liste de mes paris
        Alors je n'ai pas d'erreur http
        Quand je me déconnecte
        Et je demande la liste de mes paris
        Alors j'ai une erreur http 401

    Scénario: Je me connecte avec un login/mdp admin valide, je peux voir la vue admin
        Quand je me connecte avec admin / admin
        Alors je n'ai pas d'erreur http
        Et je demande la liste de mes paris
        Alors je n'ai pas d'erreur http
        Et je demande la vue administrateur
        Alors je n'ai pas d'erreur http
