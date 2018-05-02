var LoginViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/login/tmpl/login.html?rd='+application.getUniqueId(),

        events : {
            "keypress #user_login" : "submitOnEnter",
            "keypress #user_pass" : "submitOnEnter",
            "click #btn_sign"  : "showSignUp"
        },

        /**
         * initialisation
         */
        initialize : function() {
        },

        /**
         * rendu de la vue
         */
        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        showSignUp : function() {
          $('.form-signup').show();
          $('.form-signin').hide();
        },
        /**
         * mise en forme de la vue (création du dialog de login)
         */
        miseEnForme : function() {
            $('.form-signup').hide();

            var self = this;
            $("#loginDialog").dialog({
                title: 'Authentification',
                autoOpen: true,
                modal: false,
                width: 'auto',
                height: 'auto',
                draggable: false,
                resizable: false,
                buttons: {
                    'S\'authentifier': function (){
                        self.submitLogin();
                        return true;
                    }
                },
                open: function(event, ui) {
                  $(this).closest('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
                  $('#user_login').focus();
                  $('.ui-dialog-buttonset').parent().append("<p id='message' style='text-align:center'></p>");
                  $('#message').hide();
                },
                beforeClose: function(event, ui) {
                    return false;
                }
            });

        },

        /**
         * affiche un message de type erreur dans une boite de dialogue
         */

        afficherMessage : function(message) {
            if (message != "") {
                var dialError = new sinapsDialog();
                dialError.title('Echec de l\'authentification').modal(true)
                .onClose(function(){
                    dialError.remove();
                })
                .onOpen(function(){
                    dialError.jqObject().text(message);
                })
                .addButton('Ok', function(){
                    dialError.close();
                }).create().open();
            }
        },

        /**
         * soumission du login
         */
        submitLogin : function() {
            var self = this;
            var login = $('#user_login').val();
            var pwd = $('#user_pass').val();

            $('button').hide();
            $('#message').html('Authentification en cours...').show();

            application.login(login, pwd, function(success, errorCode) {
                if (success) {
                    $("#loginDialog").remove();
                } else {
                    self.afficherMessage(RestApi.getMessageByErrorCode(errorCode));
					$('button').show();
					$('#message').hide();
                }
            });
        },

        /**
         * fonction appelée sur un evenement keypress dans les champs login ou password
         * permet de vérifier si l'utilisateur a tapé sur "entrée" auquel cas on soumet le formulaire
         */
        submitOnEnter : function(e) {
            if (e.keyCode == 13) {
                this.submitLogin();
            }
        }
    });
    return Clazz;
}
