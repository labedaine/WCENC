# language: fr
@506
Fonctionnalité: Controle de l'authentification

    Contexte:
#        Etant donnée des requetes complexes
        Et l'application test.test.Sinaps
        Et l'application test.test.TeleIR
        Et l'utilisateur bob ayant le mot de passe bob01
        Et l'utilisateur jim ayant le mot de passe jim01
        Et l'utilisateur phil ayant le mot de passe phil01
        Et l'utilisateur jack ayant le mot de passe jack01
        Et les habilitations pour tous les projets sont les suivantes:
        | login | groupe | application | profil |
        | bob | GRP1 | Sinaps | N1 |
        | bob | GRP2 | Sinaps | administrateur |
        | bob | GRP3 | TeleIR | N0 |
        | jim | GRP1 | Sinaps | N1 |
        | jim | GRP4 | TeleIR | N0 |
        | phil | GRP5 | Sinaps | N1 |
        | phil | GRP6 | TeleIR | N1 |

    Scénario: Je me connecte avec un login/mdp valide mais aucun droit => 402
        Quand je me connecte avec jack / jack01
        Alors j'ai un code retour 402

    Scénario: Je me connecte avec un login/mdp valide, je peux récupérer les profils de l'utilisateur
        Quand je me connecte avec bob / bob01
        Et je demande la liste de mes profils
        Alors j'ai les profils
        | application   | profil       |
        | Sinaps        | administrateur  |
        | TeleIR        | N0          |

    Scénario: Je me connecte avec un login/mdp valide, je peux récupérer les groupes de l'utilisateur
        Quand je me connecte avec bob / bob01
        Et je demande la liste de mes groupes
        Alors j'ai les groupes
        | groupes   |
        | GRP1        |
        | GRP2        |
        | GRP3        |

    Scénario: Je me connecte avec un mdp invalide, je ne peux pas récupérer les profils de l'utilisateur
        Quand je me connecte avec bob / bill
        Alors j'ai un code retour 401
        Et je demande la liste de mes profils
        Alors j'ai une erreur http 401

    Scénario: Je me connecte avec un login invalide, je ne peux pas récupérer les profils de l'utilisateur
        Quand je me connecte avec bill / bob01
        Alors j'ai un code retour 401
        Et je demande la liste de mes profils
        Alors j'ai une erreur http 401

    Scénario: Je me connecte avec un login/mdp valide, je peux afficher toutes les alertes
        Quand je me connecte avec bob / bob01
        Et je demande à afficher les nouvelles alertes
        Alors je n'ai pas d'erreur http
        Et je demande à afficher les alertes en cours de traitement
        Alors je n'ai pas d'erreur http
        Et je demande à afficher l'historique des alertes
        Alors je n'ai pas d'erreur http

    Scénario: Je me connecte avec un mot de passe invalide, j'ai une erreur sur l'affichage de tous les types d'alertes
        Quand je me connecte avec bob / bill01
        Alors j'ai un code retour 401
        Et je demande à afficher les nouvelles alertes
        Alors j'ai une erreur http 401
        Et je demande à afficher les alertes en cours de traitement
        Alors j'ai une erreur http 401
        Et je demande à afficher l'historique des alertes
        Alors j'ai une erreur http 401

    Scénario: Je me connecte puis je me deconnecte, j'ai une erreur d'authentification
        Quand je me connecte avec bob / bob01
        Et je demande la liste de mes profils
        Alors j'ai les profils
        | application   | profil       |
        | Sinaps        | administrateur  |
        | TeleIR        | N0          |
        Quand je me déconnecte
        Et je demande à afficher les nouvelles alertes
        Alors j'ai une erreur http 401
        Et je demande à afficher les alertes en cours de traitement
        Alors j'ai une erreur http 401
        Et je demande à afficher l'historique des alertes
        Alors j'ai une erreur http 401

    Scénario: Je me connecte avec un login valide, mot de passe valide mais user isActif = false => je ne peux pas récupérer les profils de l'utilisateur
        Quand je me connecte avec sinaps / sinaps
        Et je demande la liste de mes profils
        Alors j'ai une erreur http 401  

    Scénario: Je me connecte avec un login/mdp valide, je veux connaître la liste des groupes de niveau N d'une application
        Quand je me connecte avec bob / bob01
        Et je demande la liste des groupes de l'application Sinaps avec le niveau 1
        Alors j'ai les groupes
        |   groupes |
        |   GRP1 |
        |   GRP5 |
