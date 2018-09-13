var application = $.extend({} , Framework, {

    // dependances javascript propres à l'application
    deps : [
        'ressource/js/config.js',                     // configuration de l'application
        'view/menu/menuView.js',                      // vue pour le menu
        'view/paris/parisView.js',                    // vue pour les paris
        'view/classement/classementView.js',          // vue pour les classement
        'view/reglement/reglementView.js',			  // vue pour le reglement
        'view/login/loginView.js',                    // vue pour le login
        'view/palmares/palmaresView.js',              // vue pour le palmares
        'view/compte/compteView.js',             	  // vue pour le compte
        'view/administration/administrationView.js',  // vue pour l'administration
        'ressource/js/popUp.js'
    ],

    // utilisateur actuellement connecte
    user : null,
    competition : null,
    equipe : null,
    pronostic : null,
    
    nePasChargerLaVue: false,

    // initialisation
    initialize : function() {
        // on initialise le framework
        this.initializeFramework();
        // pas de menu pour le moment
        this.menuView = null;
        // aucune vue courante pour le moment
        this.currentView = null;
    },

    // demarrage de l'application
    start : function() {
        // Instanciation de la classe SinapsIhm
        //jQuery.extend(this, classeSinapsIhm);
        // on essaie de s'auto logger
        // si on a un token de session en cookie : on devra afficher l'application
        // si on n'a pas de token de session en cookie : on devra afficher l'écran de login
        application.tryAutoLogin();
    },

    suite : function() {
        // on refresh l'application, ce qui va diriger vers l'affichage de l'application
        this.refresh();
        window.location = "#";
    },

    // refresh de l'application : on supprime les contenus des containers principaux et des eventuelles vues
    // on redémarre l'application
    refresh : function() {
        if (this.menuView) {
            this.menuView.destroy();
        }
        if (this.currentView) {
            this.currentView.destroy();
        }
        $('#pageContainer').html('');
        $('#menuContainer').html('');
        this.start();
    },

    // fonction d'auto log via le token de session en cookie
    // s'il y a un cookie et que le serveur le reconnait alors on redirige l'utilisateur vers une vue de l'application
    // sinon on redirige l'utilisateur vers la vue de login
    tryAutoLogin : function() {
        var self = this;
        RestApi.getCurrentUser(function(data) {
            if (data) {
                if (! data.success) {

                    // il n'y a pas de cookie ou le serveur ne le reconnait pas
                    // on redirige vers l'écran de login
                    self.user = null;
                    self.changeModule("login");

                } else {

                    // il y a un cookie et le serveur le reconnait
                    // on récupère les droits de l'utilisateur
                    self.user = new UserModelClass();
                    self.user.id = data.payload.id;
                    self.user.login = data.payload.login;
                    self.user.isadmin = data.payload.isadmin;
                    self.user.notification = data.payload.notification;
                    
                    // Info sur la competition en cours si elle existe
                    self.competition = new CompetitionModelClass();
                    self.competition.id = data.payload.competition_id;
                    self.competition.libelle = data.payload.competition_libelle;
                    self.competition.encours = data.payload.competition_encours;
                    self.competition.hasstart = data.payload.competition_hasstart;
                    self.competition.apiid = data.payload.competition_apiid;

					self.equipe = new EquipeModelClass();
					self.equipe = data.payload.equipes;
					
					self.pronostic = new PronosticModelClass();
					self.pronostic.id = data.payload.pronostic;

                    self.gotoModuleByHash();
                    self.menuView = new MenuViewClass();
                    self.menuView.render('#menuContainer');
                }
        }
        });
    },

    // fonction de login
    // appelle le serveur pour s'authentifier grace au login / password fourni
    // on peut aussi passer un callback qui sera alors appelé par la fonction login :
    //       - avec un paramètre à true si le login s'est correctement passé
    //       - avec un paramètre à false et un code d'erreur si le login ne s'est pas correctement passé
    login : function(login, password, callback) {
        var self = this;

        RestApi.login(login, password, function(data) {
            if (data) {
                if (data.success) {

                    // si la méthode a été appelée avec un callback en paramètre, on appele ce callback
                    // en indiquant que le login est passé (paramètre true)
                    if (callback) {
                        callback(true);
                    }
                } else {
                    // si la méthode a été appelée avec un callback en paramètre, on appelle ce callback
                    // en indiquant que le login n'est pas passé et le code d'erreur
                    if (callback) {
                        callback(false, data.code);
                    }
                }
            }
        });
    },

    // fonction de logout
    // on peut passer un callback qui sera alors appelé par la fonction logout :
    //       - avec un paramètre à true si le logout s'est correctement passé
    //       - avec un paramètre à false et un code d'erreur si le logout ne s'est pas correctement passé
    logout : function(callback) {
        var self = this;
        RestApi.logout(function(data) {
            if (data.success) {
                if (callback) {
                    callback(true);
                }
                // On détruit tous les cookies présents
                self.destroyAllCookie();
                $('#pageContainer').children().each( function() {$(this).remove()});
                self.user = null;
                window.location.hash="";
                history.go(0);
            } else {

                if (callback) {
                    callback(false, data.code);
                }

                // On détruit tous les cookies présents
                self.destroyAllCookie();
                $('#pageContainer').children().each( function() {$(this).remove()});
                self.user = null;
                window.location.hash="";
                self.stopCheckRefreshTimer();
                self.refresh(false);
                // BZ 121579: force le refresh de la page
                history.go(0);
            }
        });
    },

    /**
     * Dirige l'application vers la vue correspondante au hash de l'url
     */
    gotoModuleByHash : function() {
        if (application.user) {
            if (window.location.hash.length > 1) {
                if (application.nePasChargerLaVue === true) {
                    return;
                }
                var hash = window.location.hash.substring(1);
                var hashSplit = hash.split("/");
                var module = hashSplit[0];
                var args = hashSplit.slice(1);
                application.changeModule(module, args);
            } else {
                application.afficherModuleDefaut();
            }
        }
    },

    // dirige l'application vers la vue correspondante à un module
    // permet de passer des arguments à la vue via le paramètre args facultatif
    changeModule : function(module, args) {
        //console.info('changeModule: '+module);
        // s'il y a une vue en cours, on la détruit
        if (this.currentView) {
            this.currentView.destroy();
        }

        if (application.user) {
            application.user.historique_navigation = [];
        }

        if (! this.user || module === 'logout') {
            // il n'y a pas d'utilisateur authentifié, on dirige vers la vue de login
            // ou choix du menu "se déconnecter"

            this.currentView = new LoginViewClass(args);
            this.currentView.render('#pageContainer');
        } else {

            $("#menuContainer").show();
            // Affichage de la vue souhaitée
            switch (module)
            {
                // Paris
                case "paris":
                    this.afficherEcranParis(args);
                    break;
                // Paris
                case "classement":
                    this.afficherEcranClassement(args);
                    break;

                // BLOC 'Gestion des habilitations'
                case "gestion-utilisateurs": // Menu Gestion Utilisateurs
                    this.afficherEcranGestionUtilisateurs(args);
                    break;

                case "palmares": 
                    this.afficherEcranPalmares(args);
                    break;
                    
                 case "compte": 
                    this.afficherEcranCompte(args);
                    break;
                       
                // BLOC 'Reglement'
                case "reglement": // Menu reglement
                    this.afficherEcranReglement(args);
                    break;

                // BLOC 'Administration'
                case "administration": // Menu Gestion Utilisateurs
                    this.afficherEcranAdministration(args);
                    break;

                default:
                    this.afficherEcranParis();
            }

            application.afficheUnderligne();
        }

    },

    afficheUnderligne: function () {
      $('.selected').removeClass('selected');
      var selector = ".lien[href='#" + application.user.moduleEnCours + "']";
      $(selector).addClass('selected')
    },


    afficherEcranParis: function(args) {
        var module = "paris";
        application.user.moduleEnCours="paris";
        this.currentView = new ParisViewClass(args, module);
        this.currentView.render('#pageContainer');
    },
    afficherEcranClassement: function(args) {
        var module = "classement";
        application.user.moduleEnCours="classement";
        this.currentView = new ClassementViewClass(args, module);
        this.currentView.render('#pageContainer');
    },
    
	afficherEcranCompte: function(args) {
        var module = "compte";
        application.user.moduleEnCours="compte";
        this.currentView = new CompteViewClass(args, module);
        this.currentView.render('#pageContainer');
    },
    
    afficherEcranPalmares: function(args) {
        var module = "palmares";
        application.user.moduleEnCours="palmares";
        this.currentView = new PalmaresViewClass(args, module);
        this.currentView.render('#pageContainer');
    },
    
    afficherEcranReglement: function(args) {
        var module = "reglement";
        application.user.moduleEnCours="reglement";
        this.currentView = new ReglementViewClass(args, module);
        this.currentView.render('#pageContainer');
    },
    
    afficherEcranAdministration: function(args) {
        var module = "administration";
        application.user.moduleEnCours="administration";
        this.currentView = new AdministrationViewClass(args, module);
        this.currentView.render('#pageContainer');
    },

    afficherModuleDefaut : function() {
        // par defaut -> paris
        var module = "paris";
        window.location='#paris';
    },

    // fonction de mise à jour du contenu d'une vue (pour les auto refresh)
    updateModuleContent : function() {
        if (this.currentView && this.currentView.updateModuleContent) {
            this.currentView.updateModuleContent();
        }
    },

    // Détruit tous les cookies
    destroyAllCookie : function() {
        $.cookie('restitution.filters', null);
    },

    // rechargement de la page (rechargement navigateur)
    reloadPage : function(){
        location.reload(true);
    },

    getUniqueId : function() {
        var d = new Date();
        return d.getTime();
    }

});


$(document).ready(function() {
    application.initialize();
    /**
     * Navigation
     * Sur un click de menu, on recharge systèmatiquement le module,
     * même si c'est le module actif
     * Remplacement de l'événement window.onhashchange
     * -> dans menuView.js.renderView();
     */
     window.onhashchange = application.gotoModuleByHash;
});
