var LoginViewClass = function(args) {

    var Clazz= $.extend({}, ViewClass, {

        template : 'view/login/tmpl/login.html?rd='+application.getUniqueId(),

        events : {
            "click #btn_conn" : "submitLogin",
            "click #btn_connE" : "submitLogin"
        },

        timers : {"activeCarousel" : 4200},

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

        /**
         * mise en forme de la vue (création du dialog de login)
         */
        miseEnForme : function() {
            var self = this;

            $('#menuContainer').hide();
            $('#pageContainer').css('padding-top',0);

            // Carousel avec les images
            self.activeCarousel();

            $('#formInscription').validator().on('submit', function (e) {
              if (e.isDefaultPrevented()) {
                // handle the invalid form...
              } else {
                // everything looks good!
                self.submitInscription();
                return false;
              }
            });

            $("#formLogin").on('submit', function(e) {
                if (e.isDefaultPrevented()) {
                // handle the invalid form...
              } else {
                // everything looks good!
                self.submitLogin();
                return false;
              }
            });
        },

        /**
         * Gère le carousel
         */

        activeCarousel : function() {
            var currentSlide;
            var rand;
            currentSlide = Math.floor((Math.random() * $('.carousel-item').length));
            rand = currentSlide;
            $('#myCarousel').carousel(currentSlide);
            $('#myCarousel').fadeIn(4200);

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
            var login   = $('#inputLogin').val();
            var pwd     = $('#inputPassword').val();

            application.login(login, pwd, function(success, errorCode) {
                if (success) {
                    application.start();
                } else {
                    ErrorMessageBox("Ce n'est pas parce-qu'on parle de foot qu'il faut écrire avec ses pieds!",
                                    "Erreur de pseudo / Mot de passe", );
                }
            });

            return false;
        },

        /**
         * INSCRIPTION
         */

       submitInscription : function() {

            // On appelle la fonction d'enregistrement
            RestApi.enregistrerUtilisateur(

                $("#inputPseudoE").val(),
                $("#inputEmailE").val(),
                $("#inputPrenomE").val(),
                $("#inputNomE").val(),
                $("#inputPasswordE").val(),
                $("#selPromoE").val(),

                function(data) {

                    if (data) {
                        if (data.success) {
                            // On affiche une popUp qui confirme l'inscription
                            // avec le message qui va bien
                            MessageBox("Votre inscription a été prise en compte. <br/>Vous recevrez un email pour confirmer l'activation de votre compte.<br/><i>(Il sera peut-être dans les spams)</i>",
                            "Inscription" );

                        } else {
                            // PopUp erreur avec message qui va bien
                            // Si l'utilisateur existe deja
                            if(data.code == 406) {
                                var loginInput = document.querySelector("#inputPseudoE");
                                loginInput.setCustomValidity("L'identifiant n'est pas disponible");
                            } else {
                                MessageBox(data.payload);
                            }
                            return false;
                        }
                    }
            });
            return false;
       },

    });
    return Clazz;
}
