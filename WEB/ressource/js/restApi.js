var RestApi = new function() {

    var applicationContext = null;
    var errorCallbacks = {};

    var url = {
        USER : 'services/utilisateur',
        COMMUN: 'services/commun',
        ADMINISTRATION : 'services/administration'
    };

    var messageByErrorCode = {
            "401" : "Les champs \"Utilisateur\" et/ou \"Mot de passe\" sont incorrect(s)."
    };

    function getUrl(apiUrl) {
        if (applicationContext) {
            return applicationContext + "/" + apiUrl;
        } else {
            return apiUrl;
        }
    };

    function performGet(endpoint, success, error, async) {
        if (typeof async === 'undefined') {
            async = true;
        }
        var url = getUrl(endpoint);
        $.ajax({
            url : url,
            type : "GET",
            dataType : "json",
            contentType : "application/json",
            async : async,
            success : function(data) {
                if (success) {
                    success(data);
                }
            },
            error : errorHandler(error)
        });
    };
    function performPut(endpoint, data, success, error) {
        var url = getUrl(endpoint);
        $.ajax({
            url : url,
            type : "Put",
            dataType : "json",
            data : "json=" + escape(JSON.stringify(data)),
//          contentType : "application/json",
            success : function(data) {
                if (success) {
                    success(data);
                }
            },
            error : errorHandler(error)
        });
    };
    function performPost(endpoint, data, success, error) {
        var url = getUrl(endpoint);
        $.ajax({
            url : url,
            type : "Post",
            dataType : "json",
            data : "json=" + encodeURI(JSON.stringify(data)),
//          contentType : "application/json",
            success : function(data) {
                if (success) {
                    success(data);
                }
            },
            error : errorHandler(error)
        });
    };
    function performDelete(endpoint, success, error) {
        var url = getUrl(endpoint);
        $.ajax({
            url : url,
            type : "DELETE",
            dataType : "json",
            contentType : "application/json",
            success : function(data) {
                if (success) {
                    success(data);
                }
            },
            error : errorHandler(error)
        });
    };
    function errorHandler(error) {
        return function(xhr, ajaxOptions, thrownError) {
//          console.log(xhr);
//          console.log(ajaxOptions);
//          console.log(thrownError);
            statusCallBack = errorCallbacks[xhr.status];
            fallbackCallBack = errorCallbacks['fallback'];

            if (statusCallBack) {
                statusCallBack(xhr, ajaxOptions, thrownError);
            } else {
                if (error) {
                    error(xhr, ajaxOptions, thrownError);
                } else if (fallbackCallBack) {
                    fallbackCallBack(xhr, ajaxOptions, thrownError);
                }
            }
        };
    }

    return {
        setApplicationContext : function(context) {
            applicationContext = context;
        },
        getApplicationContext : function() {
            return applicationContext;
        },
        registerErrorCallbacks : function(map) {
            errorCallbacks = map;
        },
        getMessageByErrorCode : function(code) {
            if (messageByErrorCode[code]) {
                return messageByErrorCode[code];
            } else {
                return code;
            }
        },

        // ************************************
        // SERVICES COMMUNS
        // ************************************
        getMessageAccueil : function(success, error) {
            performGet(url.COMMUN  + "/messageAccueil", success, error);
        },

        // Récupère la liste des rapports en fonction du profil de l'utilisateur
        getListeRapports: function(success, error) {
            var niveauProfilUtilisateur = application.user.droits.profils.monProfil;
            performPost(url.COMMUN + "/rapports/liste", {
                            "niveauProfilUtilisateur" : niveauProfilUtilisateur
                        }, success, error);
        },
        // ************************************
        // Fonction qui retourne la liste des applications choisies à filtrer en base
        // ************************************
        getListeApplicationsChoisies : function() {
            listeDefaut = ["0"];

            if (application === null)
                return listeDefaut;

            if (application.user === null)
                return listeDefaut;

            if( application.user.mes_filtres.applicationsChoisies === null )
                return listeDefaut;

            if( application.user.mes_filtres.applicationsChoisies.length === 0 )
                return listeDefaut;

            return application.user.mes_filtres.applicationsChoisies;

        },
        // ************************************
        // AUTHENTIFICATION
        // ************************************
        login : function(login, password, success, error) {
            performPost(url.USER + "/auth", {
                "login" : login,
                "password" : password
            }, success, error);
        },
        logout : function(success, error) {
            performDelete(url.USER + "/auth", success, error);
        },
        getCurrentUser : function(success, error) {
            performGet(url.USER, success, error);
        },

        // *************************************
        // Gestion des Utilisateurs
        // *************************************
        getInfosUtilisateur : function(utilisateurId, success, error) {
            performGet(url.UTILISATEUR + "/details/"+utilisateurId, success, error);
        },
        /**
         * Enregistrement d'un utilisateur
         * les contrôles sont faits coté serveur
         * en cas c'échec, renvoie success=false + message
         * @param {type} id id technique : si = 0 ajout, sinon modification
         * @param {type} login en ajout uniquement, forcément unique
         * @param {type} nom
         * @param {type} prenom
         * @param {type} email
         * @param {type} pwd
         * @param {type} gestionHabilitations
         * @param {type} groupes liste des groupes à associer à l'utilisateur
         * @param {type} success
         * @param {type} error
         * @returns {undefined}
         */
        enregistrerUtilisateur : function(login, email, prenom, nom, pwd, promo,
                                                           success, error) {

            performPost(url.USER + "/enregistrer", {
                login : login,
                nom : nom,
                prenom : prenom,
                email : email,
                pwd : pwd,
                promo : promo
            },success, error);
        },

        isLoginInUse : function(login, success, error) {
            performPost(url.USER + "/existe", {
                login : login
            },success, error);
        },

        supprimerUtilisateur : function(id, success, error) {
            performDelete(url.USER + "/supprimer/" + id, success, error);
        },
        
        
        // *****************************************
        // Retourne la liste des utilisateur inscrit
        // *****************************************
        getListeUtilisateurs : function(success, error) {
            performPost(url.ADMINISTRATION, {}, success, error);
        },
        
        // *****************************************
        // supprime un utilisateur
        // *****************************************
        deleteUser : function(userId, success, error) {
            performPost(url.ADMINISTRATION + "/deleteUser", {
                "userId" : userId
            }, success, error);
        },
        

        // *************************************
        // AUTO REFRESH
        // *************************************
        checkForAutoRefresh : function(date, login, dateRepartitionCourante, success, error) {
            performPost(url.CHECK_AUTO_REFRESH, {date : date,
                                                 login: login,
                                                 dateRepartitionCourante: dateRepartitionCourante
                                                 }, success, error);
        },

        isApplicationExiste : function(nomColonne, valeurChamp, success, error) {
            performPost(url.APPLICATION + "/existe",
                        {
                                nomColonne : nomColonne,
                                valeurChamp : valeurChamp
                        }, success, error);
        },

    }
}
