/**
 * Classe gérant les paris
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js
 * Le click sur un menu met à jour l'url et déclenche la navigation
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée. (voir renderView)
 */
var AdministrationViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/administration/tmpl/administration.html?rd='+application.getUniqueId(),

        events : {
			"click #listeUtilisateurs" : "afficheUtilisateurs",
			"click #listeMails" : "afficheMails",
			"click #listeCompetitions" : "afficheCompetitions",
			"click #btn_enregistrerC" : "ajouterCompetition",
        },

        timers : {},

        initialize : function() {

        },

        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        miseEnForme : function() {
            // on crée les jqContainers
            this.initToolTips();
            this.getListeUtilisateurs();
            this.getListeMails();
            this.getListeCompetitions();
            this.afficheUtilisateurs();
        },

        /**
         * Fonction d'initialisation des tooltips
         */
        initToolTips : function(){

            $("a, img").tooltip({
                  content: function () {
                      return $(this).prop('title');
                  },
                  track:true
               });
        },
        
        afficheUtilisateurs: function() {
			$("#divUtilisateurs").show();
			$("#divMailsC").hide();
			$("#divCompetitionsC").hide();
			return false;
		},
		
		afficheMails: function() {
			$("#divUtilisateurs").hide();
			$("#divMailsC").show();
			$("#divCompetitionsC").hide();
			return false;
		},
		
		afficheCompetitions: function() {
			$("#divUtilisateurs").hide();
			$("#divMailsC").hide();
			$("#divCompetitionsC").show();
			return false;
		},
        
	    /**
         * get liste mails
         */
        getListeMails : function(){

        	RestApi.getListeMails(function(data) {
                 if (data.success) {
					$("#divMails").html(data.payload);
                 } else {
                     ErrorMessageBox("Impossible de charger la liste des mails");
                 }
             });
        },
        
        /**
         * get liste competitions
         */
        getListeCompetitions : function(){

        	RestApi.getListeCompetitions(function(data) {
                 if (data.success) {
					
					console.log(data.payload);
					data.payload.forEach(function(element) {
						$("#divCompet").append("<div class='row' rowCompet='" + element.id + "'></div>");
							  $("div[rowCompet="+ element.id+"]")
									.append("<div class='col-sm-2'>" + element.id + "</div>")
									.append("<div class='col-sm-4'>" + element.libelle + "</div>")
									.append("<div class='col-sm-2'>" + element.apiid + "</div>")
									.append("<div class='col-sm-3'>" + element.encours + "</div>")
									.append("<div class='col-sm-1'></div>");
					});
                 } else {
                     ErrorMessageBox("Impossible de charger la liste des competitions");
                 }
             });
        },
        
        /**
         * Fonction d'initialisation de la liste des utilisateurs
         */
        getListeUtilisateurs : function(){
        	/* {"success":true,"code":"","payload":"OK"} */
        	RestApi.getListeUtilisateurs(function(data) {
                if (data.success) {

                	 data.payload.forEach(function(element) {
                		  var isAdmin = "Non";
                		  if (element.isadmin == "1" ) {
                			  isAdmin = "Oui";
                		  }
                		  
                		  $("#divUsers").append("<div class='row' rowUser='" + element.id + "'></div>");
                		  $("div[rowUser="+ element.id+"]")
                		  		.append("<div class='col-sm-2'>" + element.nom + "</div>")
                		  		.append("<div class='col-sm-2'>" + element.prenom + "</div>")
                		  		.append("<div class='col-sm-2'>" + element.login + "</div>")
                		  		.append("<div class='col-sm-3'>" + element.email + "</div>")
                		  		.append("<div class='col-sm-1'>" + element.promotion + "</div>")
                		  		.append("<div class='col-sm-1'>" + isAdmin + "</div>");
                		  
                		  if (element.isadmin == "1" ) {
                			  $("div[rowUser="+ element.id+"]").append("<div class='col-sm-1'></div>");
                		  } else {
                			  if (element.isactif == 1) {
		                		  $("div[rowUser="+ element.id+"]")
	                		  		.append("<div class='col-sm-1'>" +
	                		  					"<i class='fas fa-trash' style='cursor:pointer;'></i>" +
	                		  				"</div>");
                			  } else {
		                		  $("div[rowUser="+ element.id+"]")
		                		  		.append("<div class='col-sm-1'>" +
		                		  					"<i class='fas fa-trash' style='cursor:pointer;'></i>" +
		                		  					"&nbsp;&nbsp;<i class='fas fa-unlock' style='cursor:pointer;'></i>" +
		                		  				"</div>");
                			  }
                		  }
                	 });
                	                    
//                	$('#testUsers').html(data.payload);
                	
                	$('.fa-trash').on('click', function() {
                		var idUser = $(this).parent().parent().attr('rowUser');
                		application.currentView.supprimerUtilisateur(idUser);
                	});
                	
                	$('.fa-unlock').on('click', function() {
                		var idUser = $(this).parent().parent().attr('rowUser');
                		application.currentView.activerUtilisateur(idUser);
                	});

                 } else {
                     ErrorMessageBox("Impossible de charger la liste des utilisateurs");
                 }
             });
        },
        
        supprimerUtilisateur : function(idUser){
        	confirm("Voulez-vous vraiment supprimer cet utilisateur ?");
        	RestApi.supprimerUtilisateur(idUser, function(data) {
        		if (data.success) {
        			$("div[rowUser=" + idUser + "]").remove();
        		} else {
                    ErrorMessageBox("Erreur lors de la suppression de l'utilisateur.");
                }
        	});
        },
        
        activerUtilisateur : function(idUser){
        	RestApi.activerUtilisateur(idUser, function(data) {
        		if (data.success) {
        			$("div[rowUser=" + idUser + "]").find('.fa-unlock').remove();
        		} else {
                    ErrorMessageBox("Erreur lors de l'activation l'utilisateur.");
                }
        	});
        },
        
        ajouterCompetition : function() {
			
			var libelle = $("#libelC").val();
			var apiid = $("#apiidC").val();
			
			RestApi.ajouterCompetition(libelle, apiid, function(data) {
        		if (data.success) {
        			MessageBox("La compétition '" + libelle + "' (" + apiid + ") ajoutée avec succès");
        		} else {
                    ErrorMessageBox("Erreur lors de l'ajout de la compétition.");
                }
        	});
		}
    });
    return Clazz;
};
