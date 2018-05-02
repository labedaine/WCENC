var LoginViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/login/tmpl/login.html?rd='+application.getUniqueId(),

        events : {
            "keypress #user_login" : "submitOnEnter",
            "keypress #user_pass" : "submitOnEnter",
            "click #btn_sign,#btn_connect"  : "flipMenuLogin",
            "click #btn_conn" : "submitLogin"
        },

        /**
         * initialisation
         */
        initialize : function() {
          $('#menuContainer').hide();
        },

        /**
         * rendu de la vue
         */
        renderView : function() {
            // on met en forme le menu
            this.miseEnForme();
        },

        flipMenuLogin : function() {
            var page1 = $('.form-signup');
            var page2 = $('.form-signin');
            var toHide = page1.is(':visible') ? page1 : page2;
            var toShow = page2.is(':visible') ? page1 : page2;

            toHide.removeClass('flip in').addClass('flip out').hide();
            toShow.removeClass('flip out').addClass('flip in').show();
            return false;
        },

        /**
         * mise en forme de la vue (création du dialog de login)
         */
        miseEnForme : function() {
            $('#menuContainer').hide();
            $('#pageContainer').css('padding-top',0);
            $('.form-signup').hide();

            var self = this;
            // $("#loginDialog").dialog({
            //     title: 'Authentification',
            //     autoOpen: true,
            //     modal: false,
            //     width: 'auto',
            //     height: 'auto',
            //     draggable: false,
            //     resizable: false,
            //     buttons: {
            //         'S\'authentifier': function (){
            //             self.submitLogin();
            //             return true;
            //         }
            //     },
            //     open: function(event, ui) {
            //       $(this).closest('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
            //       $('#user_login').focus();
            //       $('.ui-dialog-buttonset').parent().append("<p id='message' style='text-align:center'></p>");
            //       $('#message').hide();
            //     },
            //     beforeClose: function(event, ui) {
            //         return false;
            //     }
            // });

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
            var login   = $('#inputEmail').val();
            var pwd     = $('#inputPassword').val();

            $('#btn_conn').hide();
            $('#message').html('Authentification en cours...').show();

            application.login(login, pwd, function(success, errorCode) {
                if (success) {
                    application.start();
                } else {
                    self.afficherMessage(RestApi.getMessageByErrorCode(errorCode));
                    $('#btn_conn').show();
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
