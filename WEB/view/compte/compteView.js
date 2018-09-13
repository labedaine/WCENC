/**
 * Classe compte
 * La navigation se fait par l'intermédiaire de l'événement window.onhashchange (déclaré dans application.js
 * Le click sur un menu met à jour l'url et déclenche la navigation
 * Un trap du click est néanmoins nécessaire afin de raffraichir une vue déjà chargée. (voir renderView)
 */
var CompteViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/compte/tmpl/compte.html?rd='+application.getUniqueId(),

        events : {
			"click #btn_enregistrer" : "changeMdp",
			"click #btn_notification" : "setNotification",
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
            
            // Bouton notification
            this.loadText();
        },
        
        loadText : function() {
			if(application.user.notification == 1) {
				$("#btn_notification").html("Désactiver les notifications");
				$("#span_notification").html("Les notifications sont activées:<br/>Vous recevrez un mail avant la veille des journées de match.<br/><i>(Ces mails seront peut-être dans les spams)</i>");
			} else {
				$("#btn_notification").html("Activer les notifications");
				$("#span_notification").html("Les notifications sont désactivées.");
			}
		},
        
        changeMdp : function() {

            var self = this;

            var ancienPwd     = $('#inputPasswordC').val();
            var ancienPwd2    = $('#inputCPasswordC').val();
            var nouveauPwd    = $('#inputCPasswordN').val();
            
            if(ancienPwd != ancienPwd2) {
				ErrorMessageBox("Les mots de passe ne sont pas identiques");
				return false;
			}
			
			RestApi.changeMdp(application.user.id, ancienPwd, nouveauPwd, function(data) {
				if (data.success) {
                    MessageBox("Votre mot de passe a été modifié avec succès");
                    
                } else {
                    ErrorMessageBox(data.payload);
                    return false;
                }
            });
			
            return false;
        },
        
        setNotification : function() {

            var self = this;
            var notification = (application.user.notification == 1 ? 0 : 1);

			RestApi.setNotification(notification, function(data) {
				if (data.success) {
                    MessageBox("La modification a été prise en compte avec succès.");
                    application.user.notification = notification;
                    self.loadText();
                } else {
                    ErrorMessageBox(data.payload);
                    return false;
                }
            });
			
            return false;
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
    });
    return Clazz;
};
