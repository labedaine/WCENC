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
                		  
                		  $("#divUsers").append("<div class='row' rowUser='" + element.id + "' name='a'></div>");
                		  $("div[rowUser="+ element.id+"]")
                		  		.append("<div class='col-sm'>" + element.nom + "</div>")
                		  		.append("<div class='col-sm'>" + element.prenom + "</div>")
                		  		.append("<div class='col-sm'>" + element.login + "</div>")
                		  		.append("<div class='col-sm'>" + element.email + "</div>")
                		  		.append("<div class='col-sm'>" + isAdmin + "</div>")
                		  		.append("<div class='col-sm'>" +
                		  					"<i class='fas fa-edit' style='cursor:pointer;'></i>&nbsp;&nbsp;" +
                		  					"<i class='fas fa-trash' style='cursor:pointer;'></i>" +
                		  				"</div>");
                	 });
                	
                    
                	$('#testUsers').html(data.payload);
                	
                	$('.fa-trash').on('click', function() {
                		var idUser = $(this).parent().parent().attr('rowUser');
                		application.currentView.deleteUser(idUser);
                	});

                 } else {
                     ErrorMessageBox("Impossible de charger la liste des utilisateurs");
                 }
             });
        },
        
        deleteUser : function(idUser){
        	confirm("Voulez-vous vraiment supprimer cet utilisateur ?");
        	RestApi.supprimerUtilisateur(idUser, function(data) {
        		if (data.success) {
        			$("div[rowUser=" + idUser + "]").remove();
        		} else {
                    ErrorMessageBox("Erreur lors de la suppression de l'utilisateur.");
                }
        	});
        },
    });
    return Clazz;
};
