var UserModelClass = function() {

	var Clazz = $.extend({}, ModelClass, {

		id : '',
		name : '',
		droits : 		{},
                // Préférences de l'uitlisateur : contient un tableau de clef -> valeur
                // Contient notamment la homepage de l'IHMR
                preferences : {},
                // mes filtres : filtres utilisés pendant la session utilisateur :
                //  - les applications
                //  - les statuts
                //  - les tags d'indicateurs
                mes_filtres: {
                        applications: [],  // Liste des ids d'applications
                        statuts: {
                                'OK': true,
                                'WARNING': true,
                                'CRITICAL': true,
                                'UNKNOWN': true
                              },
                        tags: []
                    },

                // Historique/liste de navigation des vues surgissantes successives
                historique_navigation: [],

		// valeur du niveau d'un profil qu'il faut avoir au minimum pour pouvoir "acceder" a une application
		niveauPourAccesApplication : 0,

		initialize : function() {},

		/**
		 * indique si l'utilisateur a access a un module
		 */
		hasModuleAccess : function(moduleToCheck) {
                    for (module in this.droits.modules) {
//                        console.info('hasModuleAccess : '+ this.droits.modules[module].module);
                        if (this.droits.modules[module].module === moduleToCheck) {
                            return true;
                        }
                    }
                    return false;
		},
		/**
		 * indique si l'utilisateur a access en ecriture a un module
		 */
		hasWriteModuleAccess : function(moduleToCheck) {
                    for (module in this.droits.modules) {
                        if (this.droits.modules[module].module === moduleToCheck) {
                            if (this.droits.modules[module].access === "rw") {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                    return false;
		},
		/**
		 * retourne la liste des applications accessibles a un utilisateur et son profil pour chaque
		 * les applications accessibles sont les applications pour lesquelles l'utilisateur a un profil dont le
		 * niveau est inferieur a la valeur de this.niveauPourAccesApplication
		 * (plus le niveau d'un profil est faible plus le profil est "puissant")
		 */
		getApplicationsAccessibles : function() {
                    var self = this;
                    var applicationsAccessibles = [];
                    if (this.droits.profils !== undefined) {
                        $.each(this.droits.profils, function() {

                            if (this.niveau >= self.niveauPourAccesApplication) {
                                // l'utilisateur a un profil avec un niveau supérieur a niveauPourAccesApplication
                                // il a donc acces a l'application (plus le niveau est fort plus le profil est "puissant")
                                applicationsAccessibles.push(this);
                            }
                        });
                    }

			return applicationsAccessibles;
		},
		/**
		 * indique si l'utilisateur possede un profil dont le niveau est <= au niveau passe en parametre
		 * pour l'application passee en parametre
		 * (plus le niveau d'un profil est faible plus le profil est "puissant")
		 */
                estDeNiveauSuffisantSurApplication : function(niveau, applicationId) {
                    var estDeNiveauSuffisant = false;
                    $.each(this.droits.profils, function() {
                        if (this.id == applicationId && this.niveau >= niveau) {
                            estDeNiveauSuffisant = true;
                        }
                    });
                    return estDeNiveauSuffisant;
                },
		/**
		 * retourne la liste des tags accessibles a l'utilisateur
		 */
		getTagsAccessibles : function() {
                    return this.droits.tags;
		}
	});
	return Clazz;
}
